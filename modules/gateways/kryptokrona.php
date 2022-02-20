<?php
if (!defined("WHMCS")) {
    die("This file cannot be accessed directly");
}



function kryptokrona_MetaData()
{
    return array(
        'DisplayName' => 'Kryptokrona',
        'APIVersion' => '1.1', // Use API Version 1.1
        'DisableLocalCredtCardInput' => true,
        'TokenisedStorage' => false,
    );
}
function kryptokrona_Config(){
	return array(
		'FriendlyName' => array('Type' => 'System','Value' => 'Kryptokrona'),
		'address' => array('FriendlyName' => 'Kryptokrona Address','Type' => 'text','Size' => '94','Default' => '','Description' => 'Not used yet'),
		'secretkey' => array('FriendlyName' => 'Module Secret Key','Type' => 'text','Default' => '21ieudgqwhb32i7tyg','Description' => 'Enter a unique key to verify callbacks'),
		'daemon_host' => array('FriendlyName' => 'Wallet RPC Host','Type' => 'text','Default' => 'localhost','Description' => 'Connection settings for the Kryptokrona Wallet RPC daemon.'),
		'daemon_port' => array('FriendlyName' => 'Wallet RPC Port','Type'  => 'text','Default' => '','Description' => ''),
		'daemon_user' => array('FriendlyName' => 'Wallet RPC Username','Type'  => 'text','Default' => '','Description' => ''),
		'daemon_pass' => array('FriendlyName' => 'Wallet RPC Password','Type'  => 'text','Default' => '','Description' => ''),
		'discount_percentage' => array('FriendlyName' => 'Discount Percentage','Type'  => 'text','Default' => '0%','Description' => 'Percentage discount for paying with Kryptokrona.')
    );
}

/*
*  
*  Get the current XKR price in several currencies
*  
*  @param String $currencies  List of currency codes separated by comma
*  
*  @return String  A json string in the format {"CURRENCY_CODE":PRICE}
*  
*/
function kryptokrona_retrivePriceList($currencies = 'USD') {
	
	$source = 'https://api.coinpaprika.com/v1/tickers/xkr-kryptokrona';
	
	if (ini_get('allow_url_fopen')) {
		
		return file_get_contents($source);
		
	}
	
	if (!function_exists('curl_init')) {
		
		echo 'cURL not available.';
		
		return false;
		
	}
	
	$options = array (
		CURLOPT_URL            => $source,
		CURLOPT_RETURNTRANSFER => true,
		CURLOPT_CONNECTTIMEOUT => 10,
		CURLOPT_TIMEOUT        => 20,
	);
	
	$ch = curl_init();
	curl_setopt_array($ch, $options);
	
	$response = curl_exec($ch);
	
	curl_close($ch);
	
	if ($response === false) {
		
		echo 'Error while retrieving XKR price list';
		
	}
	
	return $response;
	
}

function kryptokrona_retriveprice($currency) {
	global $currency_symbol;
	$response = kryptokrona_retrivePriceList('USD');
    $responseBody = json_decode($response);
	if($currency == 'XKR'){
		$currency_symbol = 'XKR';
		$price = $responseBody->{'quotes'}->{'USD'}->{'price'};
//		echo "The price is {$price}";
		if(!isset($price)){
			echo "There was an error";
		}
		return $price;
	}
}

function kryptokrona_changeto($amount){
    $xkr_live_price = kryptokrona_retriveprice('XKR');
	$live_for_storing = $xkr_live_price * 100; //This will remove the decimal so that it can easily be stored as an integer
	$new_amount = $amount / $xkr_live_price;
	$rounded_amount = round($new_amount, 2);
    return $rounded_amount;
}

function xkr_to_fiat($amount){
    $xkr_live_price = kryptokrona_retriveprice('XKR');
    $amount = $amount / 100000;
	$new_amount = $amount * $xkr_live_price;
	$rounded_amount = round($new_amount, 0);
    return $rounded_amount;
}



function kryptokrona_link($params){
global $currency_symbol;

$gatewaymodule = "kryptokrona";
$gateway = getGatewayVariables($gatewaymodule);
if(!$gateway["type"]) die("Module not activated");


	$invoiceid = $params['invoiceid'];
	$amount = $params['amount'];
	$discount_setting = $gateway['discount_percentage'];
	$discount_percentage = 100 - (preg_replace("/[^0-9]/", "", $discount_setting));
	$amount = money_format('%i', $amount * ($discount_percentage / 100));
	$currency = $params['USD'];
	$firstname = $params['clientdetails']['firstname'];
	$lastname = $params['clientdetails']['lastname'];
	$email = $params['clientdetails']['email'];
	$city = $params['clientdetails']['city'];
	$state = $params['clientdetails']['state'];
	$postcode = $params['clientdetails']['postcode'];
	$country = $params['clientdetails']['country'];
	//$address = $params['address'];
	$systemurl = $params['systemurl'];
    // Transform Current Currency into Kryptokrona
	$amount_xkr = kryptokrona_changeto($amount, $currency);
	
	$post = array(
        'invoice_id'    => $invoiceid,
        'systemURL'     => $systemurl,
        'buyerName'     => $firstname . ' ' . $lastname,
        'buyerAddress1' => $address1,
        'buyerAddress2' => $address2,
        'buyerCity'     => $city,
        'buyerState'    => $state,
        'buyerZip'      => $postcode,
        'buyerEmail'    => $email,
        'buyerPhone'    => $phone,
        'address'       => $address,
        'amount_xkr'    => $amount_xkr,
        'amount'        => $amount,
        'currency'      => 'USD'     
    );
	$form = '<form action="' . $systemurl . 'kryptokrona/createinvoice.php" method="POST">';
    foreach ($post as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value = "' . $value .'" />';
    }
    $form .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $form .= '</form>';
	$form .= '<p>'.$amount_xkr. " XKR (". 'USD' . $amount . " " . $currency .')</p>';
	if ($discount_setting > 0) {
		$form .='<p><small>Discount Applied: ' . preg_replace("/[^0-9]/", "", $discount_setting) . '% </small></p>';
	}
    return $form;
}
