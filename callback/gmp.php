<?php

# Required File Includes
include("../../../dbconnect.php");
include("../../../includes/functions.php");
include("../../../includes/gatewayfunctions.php");
include("../../../includes/invoicefunctions.php");
include("../gmp/mercadopago.php");

$gatewaymodule = "gmp"; # Enter your gateway module name here replacing template

$GATEWAY = getGatewayVariables($gatewaymodule);
if (!$GATEWAY["type"]) die("Module Not Activated"); # Checks gateway module is active before accepting callback

$mp = new MP($GATEWAY['client_id'], $GATEWAY['client_secret']);
if ($GATEWAY['testmode']) {
  $mp->sandbox_mode(TRUE);
}
$payment_info = $mp->get_payment_info($_GET["id"]);
$debug = array('json' => json_encode($payment_info));
$status = "0";
if ($payment_info["status"] == 200) {
  $data = $payment_info["response"]["collection"];
  if (!$data["sandbox"] && "approved"==$data["status"]) {
    $transid = $data["id"];
    $invoiceid = $data["external_reference"];
    $amount = (float)$data["transaction_amount"];
    $fee = (float)$data["transaction_amount"]-(float)$data["net_received_amount"];
    $status = "1";
  }
}

if ($status=="1") {
  # Successful
  $invoiceid = checkCbInvoiceID($invoiceid,$gatewaymodule); # Checks invoice ID is a valid invoice number or ends processing
  checkCbTransID($transid); # Checks transaction number isn't already in the database and ends processing if it does
  addInvoicePayment($invoiceid,$transid,$amount,$fee,$gatewaymodule); # Apply Payment to Invoice: invoiceid, transactionid, amount paid, fees, modulename
	logTransaction($GATEWAY["name"],$debug,"Successful"); # Save to Gateway Log: name, data array, status
} else {
	# Unsuccessful
  logTransaction($GATEWAY["name"],$debug,"Unsuccessful"); # Save to Gateway Log: name, data array, status
}

?>
