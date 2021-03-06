<?php
error_reporting(E_ALL);
//die('here3');
session_start();

session_unset();
/*
 * Use the Pay API operation to transfer funds from a sender�s PayPal account to one or more receivers� PayPal accounts. You can use the Pay API operation to make simple payments, chained payments, or parallel payments; these payments can be explicitly approved, preapproved, or implicitly approved.

  Use the Pay API operation to transfer funds from a sender's PayPal account to one or more receivers' PayPal accounts. You can use the Pay API operation to make simple payments, chained payments, or parallel payments; these payments can be explicitly approved, preapproved, or implicitly approved.

  A chained payment is a payment from a sender that is indirectly split among multiple receivers. It is an extension of a typical payment from a sender to a receiver, in which a receiver, known as the primary receiver, passes part of the payment to other receivers, who are called secondary receivers

 * Create your PayRequest message by setting the common fields. If you want more than a simple payment, add fields for the specific kind of request, which include parallel payments, chained payments, implicit payments, and preapproved payments.
 */
if(file_exists('../../../wp-config.php')){
    require_once('../../../wp-config.php');
    global $wpdb;
}
require_once('../PPBootStrap.php');
require_once('../Common/Constants.php');
define("DEFAULT_SELECT", "- Select -");

$returnUrl = "https://aawesomeme.com/paypal/chained-payment/success.php";
$cancelUrl = "https://aawesomeme.com/paypal/chained-payment/index.php";
$memo = "Adaptive Payment - chained Payment";
$actionType = "PAY";
$currencyCode = "USD";

//$string = var_dump( file_get_contents('php://input'));
//echo 'here';
//$json_a = json_decode($string, true);
//echo '<pre>'; print_r($json_a); die();
//$redata = var_dump($_REQUEST);
//echo '<pre>'; print_r($redata); die;
$productid=$_REQUEST['productid'];
$price=$_REQUEST['price'];
$productname=$_REQUEST['name'];
$userid=$_REQUEST['userid'];
$quantity=$_REQUEST['quantity'];
$sellerid=$_REQUEST['sellerid'];

//$redata->price = 100;
if ($price != '') {
    $seller_data = $wpdb->get_row("SELECT * FROM wp_users WHERE ID = '".$sellerid."'");
    
     $seller_eamil = $seller_data->paypal_email; 
        
    $receiverEmail = array( 'rupak1-facilitator@avainfotech.com' , "vikrant-facilitator@avainfotech.com" );
    
    $totalamount = $price;
    $adminamount = (10 / 100) * $totalamount;
    $selleramount = $totalamount-$adminamount;
    
    $receiverAmount = array($price, $selleramount); 
    $primaryReceiver = array("true", "false");
}

if (isset($receiverEmail)) {
    $receiver = array();
    /*
     * A receiver's email address 
     */
    for ($i = 0; $i < count($receiverEmail); $i++) {
        $receiver[$i] = new Receiver();
        $receiver[$i]->email = $receiverEmail[$i];
        /*
         *  	Amount to be credited to the receiver's account 
         */
        $receiver[$i]->amount = $receiverAmount[$i];
        /*
         * Set to true to indicate a chained payment; only one receiver can be a primary receiver. Omit this field, or set it to false for simple and parallel payments. 
         */
        $receiver[$i]->primary = $primaryReceiver[$i];
    }
    $receiverList = new ReceiverList($receiver);
}

/*
 * The action for this request. Possible values are:

  PAY - Use this option if you are not using the Pay request in combination with ExecutePayment.
  CREATE - Use this option to set up the payment instructions with SetPaymentOptions and then execute the payment at a later time with the ExecutePayment.
  PAY_PRIMARY - For chained payments only, specify this value to delay payments to the secondary receivers; only the payment to the primary receiver is processed.

 */
/*
 * The code for the currency in which the payment is made; you can specify only one currency, regardless of the number of receivers 
 */
/*
 * URL to redirect the sender's browser to after canceling the approval for a payment; it is always required but only used for payments that require approval (explicit payments) 
 */
/*
 * URL to redirect the sender's browser to after the sender has logged into PayPal and approved a payment; it is always required but only used if a payment requires explicit approval 
 */
$payRequest = new PayRequest(new RequestEnvelope("en_US"), $actionType, $cancelUrl, $currencyCode, $receiverList, $returnUrl);
// Add optional params

if ($memo != "") {
    $payRequest->memo = $memo;
}



/*
 * 	 ## Creating service wrapper object
  Creating service wrapper object to make API call and loading
  Configuration::getAcctAndConfig() returns array that contains credential and config parameters
 */
$service = new AdaptivePaymentsService(Configuration::getAcctAndConfig());
try {
    /* wrap API method calls on the service object with a try catch */
    $response = $service->Pay($payRequest);
    $ack = strtoupper($response->responseEnvelope->ack);
    if ($ack == "SUCCESS") {
        $payKey = $response->payKey;
        $_SESSION['pay_key']=$payKey;
       // $payPalURL = PAYPAL_REDIRECT_URL . '_ap-payment&paykey=' . $payKey;
       // $payPalURL = PAYPAL_REDIRECT_URL . 'webapps/paypal-adaptive/flow/pay?paykey=' . $payKey . '&expType=mini';
        $payPalURL = PAYPAL_REDIRECT_URL . 'webapps/adaptivepayment/flow/pay?paykey=' . $payKey . '&expType=mini';


        header('Location:' . $payPalURL);
    }
} catch (Exception $ex) {
    require_once '../Common/Error.php';
    exit;
}
/* Make the call to PayPal to get the Pay token
  If the API call succeded, then redirect the buyer to PayPal
  to begin to authorize payment.  If an error occured, show the
  resulting errors */
?>
