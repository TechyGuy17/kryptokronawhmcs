<?php

include ($_SERVER["DOCUMENT_ROOT"] . "/init.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/functions.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/gatewayfunctions.php");
include ($_SERVER["DOCUMENT_ROOT"] . "/includes/invoicefunctions.php");
//include("$rootDir/../init.php"); 
//include("$rootDir/../includes/functions.php");
//include("$rootDir/../includes/gatewayfunctions.php");
//include("$rootDir/../includes/invoicefunctions.php");


$gatewaymodule = "kryptokrona";
$GATEWAY = getGatewayVariables($gatewaymodule);
if(!$GATEWAY["type"]) die("Module not activated");
require_once('library.php');

$link = $GATEWAY['daemon_host'].":".$GATEWAY['daemon_port']."/json_rpc";


function kryptokrona_paymentID(){
    if(!isset($_COOKIE['paymentID'])) { 
		$paymentID  = bin2hex(openssl_random_pseudo_bytes(33));
		setcookie('paymentID', $paymentID, time()+2700);
	} else {
		$paymentID = $_COOKIE['paymentID'];
    }
		return $paymentID;
	
}

$kryptokrona_daemon = new Kryptokrona_rpc($link);

$message = "Waiting for your payment.";
$_POST  = filter_input_array(INPUT_POST, FILTER_SANITIZE_STRING);
$currency = stripslashes($_POST['currency']);
$amount_xkr = stripslashes($_POST['amount_xkr']);
$amount = stripslashes($_POST['amount']);
$paymentID = kryptokrona_paymentID();
$invoice_id = stripslashes($_POST['invoice_id']);
$array_integrated_address = $kryptokrona_daemon->addresses;
$address = $kryptokrona_daemon->addresses;
$uri  =  "xkr://$address?amount=$amount_xkr?paymentID=$paymentID";
echo $address;

$secretKey = $GATEWAY['secretkey'];
$hash = md5($invoice_id . $paymentID . $amount_xkr . $secretKey);
echo "<link href='/kryptokrona/style.css' rel='stylesheet'>";
echo  "<script src='https://code.jquery.com/jquery-3.2.1.min.js'></script>";
echo  "<script src='/kryptokrona/spin.js'></script>";
//echo $address;

echo "<title>Invoice</title>";
echo "<head>
        <!--Import Google Icon Font-->
        <link href='https://fonts.googleapis.com/icon?family=Material+Icons' rel='stylesheet'>
        <link href='https://fonts.googleapis.com/css?family=Montserrat:400,800' rel='stylesheet'>
        <!--Let browser know website is optimized for mobile-->
            <meta name='viewport' content='width=device-width, initial-scale=1.0'/>
            </head>
            <body>
            <!-- page container  -->
            <div class='page-container'>
                <img src='/kryptokrona/kryptokrona.png' width='200' />

        <div class='progress' id='progress'></div>

			<script>
				var opts = {
					lines: 10, // The number of lines to draw
					length: 7, // The length of each line
					width: 4, // The line thickness
					radius: 10, // The radius of the inner circle
					corners: 1, // Corner roundness (0..1)
					rotate: 0, // The rotation offset
					color: '#000', // #rgb or #rrggbb
					speed: 1, // Rounds per second
					trail: 60, // Afterglow percentage
					shadow: false, // Whether to render a shadow
					hwaccel: false, // Whether to use hardware acceleration
					className: 'spinner', // The CSS class to assign to the spinner
					zIndex: 2e9, // The z-index (defaults to 2000000000)
					top: 25, // Top position relative to parent in px
					left: 0 // Left position relative to parent in px
				};
				var target = document.getElementById('progress');
				var spinner = new Spinner(opts).spin(target);
			</script>
			
        <div id='container'></div>
        	    <div class='alert alert-warning' id='message'>".$message."</div><br>
          <!-- kryptokrona container payment box -->
            <div class='container-xmr-payment'>
            <!-- header -->
            <div class='header-xmr-payment'>
            <span class='xmr-payment-text-header'><h2>KRYPTOKRONA PAYMENT</h2></span>
            </div>
            <!-- end header -->
            <!-- xmr content box -->
            <div class='content-xmr-payment'>
            <div class='xmr-amount-send'>
            <span class='xmr-label'>Send:</span>
            <div class='xmr-amount-box'>".$amount_xkr." XKR ($" . $amount . " " . $currency .") </div><div class='xmr-box'>XKR</div>
            </div>
            <div class='xmr-address'>
            <span class='xmr-label'>To this address:</span>
            <div class='xmr-address-box'>". $address."</div>
            </div>
            <div class='xmr-qr-code'>
            <span class='xmr-label'>Or scan QR:</span>
            <div class='xmr-qr-code-box'><img src='https://api.qrserver.com/v1/create-qr-code/? size=200x200&data=".$uri."' /></div>
            </div>
            <div class='clear'></div>
            </div>
            <!-- end content box -->
            <!-- footer xmr payment -->
            <div class='footer-xmr-payment'>
            </div>
            <!-- end footer xmr payment -->
            </div>
            <!-- end kryptokrona container payment box -->
            </div>
            <!-- end page container  -->
            </body>
        ";
	    

echo "<script> function verify(){ 

$.ajax({ url : 'verify.php',
	type : 'POST',
	data: { 'amount_xkr' : '".$amount_xkr."', 'paymentID' : '".$paymentID."', 'invoice_id' : '".$invoice_id."', 'amount' : '".$amount."', 'hash' : '".$hash."', 'currency' : '".$currency."'}, 
	success: function(msg) {
		console.log(msg);
		$('#message').text(msg);
		if(msg=='Payment has been received.') {
			//redirect to Paid invoice
            window.location.href = '/viewinvoice.php?id=$invoice_id';
		}
	},									
   error: function (req, status, err) {
        $('#message').text(err);
        console.log('Something went wrong', status, err);
        
    }
	
	
			}); 
} 
verify();
setInterval(function(){ verify()}, 5000);
</script>";
?>
