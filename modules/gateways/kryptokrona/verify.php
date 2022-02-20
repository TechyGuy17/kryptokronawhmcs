<?php

include ($_SERVER["DOCUMENT_ROOT"] . "/init.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/functions.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/gatewayfunctions.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/invoicefunctions.php");

function exceptions_error_handler($severity, $message, $filename, $lineno) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
}
use Illuminate\Database\Capsule\Manager as Capsule;

$fee = "0.0";
$status = "unknown";
$gatewaymodule = "kryptokrona";
$GATEWAY = getGatewayVariables($gatewaymodule);

$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$invoice_id = $_POST['invoice_id'];
$paymentID = $_POST['paymentID'];
$amount_xkr = $_POST['amount_xkr'];
$amount = $_POST['amount'];
$hash = $_POST['hash'];
$currency = $_POST['currency'];


$secretKey = $GATEWAY['secretkey'];
$link = $GATEWAY['daemon_host'].":".$GATEWAY['daemon_port']."/json_rpc";

require_once('library.php');

function verify_payment($paymentID, $amount, $amount_xkr, $invoice_id, $fee, $status, $gatewaymodule, $hash, $secretKey, $currency){
	global $currency_symbol;
	echo 'Soon connected!';
	$kryptokrona_daemon = new Kryptokrona_rpc($link);
	echo 'Connected!';
	$check_mempool = true;
	//Checks invoice ID is a valid invoice number 
	$invoice_id = checkCbInvoiceID($invoice_id, $gatewaymodule);

	if ($paymentID !="") {
		//Validate callback authenticity
		if ($hash != md5($invoice_id . $paymentID . $amount_xkr . $secretKey)) {
			return 'Hash Verification Failure';
		}
		$message = "Waiting for your payment.";

 		//paymentID is sometimes empty

		// send each kryptokrona tx in the mempool to handle_whmcs
		if ($check_mempool) {
			$get_payments_method = $kryptokrona_daemon->get_transfers('pool', true);
			foreach ($get_payments_method["pool"] as $tx => $transactions) {
				$txn_amt = $transactions["amount"];
				$txn_txid = $transactions["txid"];
				$txn_paymentID = $transactions["paymentID"];
				if(isset($txn_amt)) { 
					return handle_whmcs($invoice_id, $amount_xkr, $txn_amt, $txn_txid, $txn_paymentID, $paymentID, $currency, $gatewaymodule);
				}
			}
		}
		// send each kryptokrona tx to handle_whmcs
		$get_payments_method = $kryptokrona_daemon->get_payments($paymentID);
		foreach ($get_payments_method["payments"] as $tx => $transactions) {
			$txn_amt = $transactions["amount"];
			$txn_txid = $transactions["tx_hash"];
			$txn_paymentID = $transactions["paymentID"];
			if(isset($txn_amt)) { 
				return handle_whmcs($invoice_id, $amount_xkr, $txn_amt, $txn_txid, $txn_paymentID, $paymentID, $currency, $gatewaymodule);
			}
		}
	} else {
		return "Error: No payment ID.";
	}
	return $message;
} 

function handle_whmcs($invoice_id, $amount_xkr, $txn_amt, $txn_txid, $txn_paymentID, $paymentID, $currency, $gatewaymodule) {
	$amount_atomic_units = $amount_xkr * 100000;
	
	//check if kryptokrona tx already exists in whmcs 
	$record = Capsule::table('tblaccounts')->where('transid', $txn_txid)->get();
	$transaction_exists = $record[0]->transid;
	if ($txn_paymentID == $paymentID) {
		if (!$transaction_exists) {
			//check one more time then add the payment if the transaction has not been added.
			checkCbTransID($txn_txid);
			$fiat_paid = xkr_to_fiat($txn_amt, $currency);
			add_payment("AddInvoicePayment", $invoice_id, $txn_txid, $gatewaymodule, $fiat_paid, $txn_amt / 100000, $paymentID, $fee);
		}
		// add 2% when doing the comparison in case of price fluctuations?
		if ($txn_amt * 1.02 >= $amount_atomic_units) {
			return "Payment has been received.";
		} else {
			return "Error: Amount " . $txn_amt / 100000 . " XKR too small. Please send full amount or contact customer service. Transaction ID: " . $txn_txid . ". Payment ID: " . $paymentID;
		}
	}
}

function add_payment($command, $invoice_id, $txn_txid, $gatewaymodule, $fiat_paid, $amount_xkr, $paymentID, $fee) {
	$postData = array(
		'action' => $command,
		'invoiceid' => $invoice_id,
		'transid' => $txn_txid,
		'gateway' => $gatewaymodule,
		'amount' => $fiat_paid,
		'amount_xkr' => $amount_xkr,
		'paymentid' => $paymentID,
		'fees' => $fee,
	);
	// Add the invoice payment - either of the next two lines work
	// $results = localAPI($command, $postData, $adminUsername);
    	addInvoicePayment($invoice_id,$txn_txid,$fiat_paid,$fee,$gatewaymodule);
	logTransaction($gatewaymodule, $postData, "Success: ".$message);
}


/*
function stop_payment($paymentID, $amount, $invoice_id, $fee, $link){
	$verify = verify_payment($paymentID, $amount, $invoice_id, $fee, $link);
	if($verify){
		$message = "Payment has been received and confirmed.";
	}
	else{
		$message = "We are waiting for your payment to be confirmed";
	}
} */

//$verify = verify_payment($paymentID, $amount, $amount_xkr, $invoice_id, $fee, $status, $gatewaymodule, $hash, $secretKey, $currency);
echo $verify;
set_error_handler('exceptions_error_handler');
?>
