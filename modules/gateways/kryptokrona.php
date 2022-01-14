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
*  Get the current XMR price in several currencies
*  
*  @param String $currencies  List of currency codes separated by comma
*  
*  @return String  A json string in the format {"CURRENCY_CODE":PRICE}
*  
*/
function kryptokrona_retrivePriceList($currencies = 'BTC,USD,EUR,CAD,INR,GBP,BRL') {
	
	$source = 'https://api.coingecko.com/api/v3/simple/price?ids=kryptokrona&vs_currencies=$currencies';
	
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
	
	$xkr_price = curl_exec($ch);
	
	curl_close($ch);
	
	if ($xkr_price === false) {
		
		echo 'Error while retrieving XKR price list';
		
	}
	
	return $xkr_price;
	
}

function kryptokrona_retriveprice($currency) {
	global $currency_symbol;
	$xkr_price = kryptokrona_retrivePriceList('BTC,USD,EUR,CAD,INR,GBP,BRL');
    $price = json_decode($xkr_price, TRUE);
	if(!isset($price)){
		echo "There was an error";
	}
	if ($currency == 'USD') {
		$currency_symbol = "$";
		return $price['USD'];
	}
	if ($currency == 'EUR') {
		$currency_symbol = "€";
		return $price['EUR'];
	}
	if ($currency == 'CAD'){
		$currency_symbol = "$";
		return $price['CAD'];
	}
	if ($currency == 'GBP'){
		$currency_symbol = "£";
		return $price['GBP'];
	}
	if ($currency == 'INR'){
		$currency_symbol = "₹";
		return $price['INR'];
	}
	if ($currency == 'BRL'){
		$currency_symbol = "R$ ";
		return $price['BRL'];
	}
	if($currency == 'XKR'){
		$price = '1';
		return $price;
	}
}

function kryptokrona_changeto($amount, $currency){
    $xkr_live_price = kryptokrona_retriveprice($currency);
	$live_for_storing = $xkr_live_price * 100; //This will remove the decimal so that it can easily be stored as an integer
	$new_amount = $amount / $xkr_live_price;
	$rounded_amount = round($new_amount, 12);
    return $rounded_amount;
}

function xkr_to_fiat($amount, $currency){
    $xkr_live_price = kryptokrona_retriveprice($currency);
    $amount = $amount / 100000;
	$new_amount = $amount * $xkr_live_price;
	$rounded_amount = round($new_amount, 2);
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
	$currency = $params['currency'];
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
        'currency'      => $currency     
    );
	$form = '<form action="' . $systemurl . '/modules/gateways/kryptokrona/createinvoice.php" method="POST">';
    foreach ($post as $key => $value) {
        $form .= '<input type="hidden" name="' . $key . '" value = "' . $value .'" />';
    }
    $form .= '<input type="submit" value="' . $params['langpaynow'] . '" />';
    $form .= '</form>';
	$form .= '<p>'.$amount_xkr. " XKR (". $currency_symbol . $amount . " " . $currency .')</p>';
	if ($discount_setting > 0) {
		$form .='<p><small>Discount Applied: ' . preg_replace("/[^0-9]/", "", $discount_setting) . '% </small></p>';
	}
    return $form;
}
