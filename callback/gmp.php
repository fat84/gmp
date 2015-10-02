<?php

use WHMCS\Module\Gateway;
use WHMCS\Terminus;

/** @type WHMCS\Application $whmcs */

# Required File Includes
include "../../../init.php";
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/functions.php';
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/gatewayfunctions.php';
include ROOTDIR . DIRECTORY_SEPARATOR . 'includes/invoicefunctions.php';
include("../gmp/mercadopago.php");

$gatewayModule = 'gmp'; # Enter your gateway module name here replacing template

/**
 * Ensure that the module is active before attempting to run any code
 */
$gateway = new Gateway();
if (!$gateway->isActiveGateway($gatewayModule) || !$gateway->load($gatewayModule)) {
    Terminus::getInstance()->doDie('Module not Active');
}

$GATEWAY = $gateway->getParams();

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
    $transactionId = $data["id"];
    $invoiceId = $data["external_reference"];
    $amount = (float)$data["transaction_amount"];
    $fee = (float)$data["transaction_amount"]-(float)$data["net_received_amount"];
    $status = "1";
  }
}

if ($status=="1") {
    /**
     * Check the invoice id is valid or die
     */
    $invoiceId = checkCbInvoiceID($invoiceId, $GATEWAY["name"]);

    /**
     * Check transaction Id is unique or die
     */
    checkCbTransID($transactionId);

    /**
     * Successful payment.
     * Apply Payment to Invoice: invoiceId, transactionId, amount paid, fees, moduleName
     */
    addInvoicePayment(
        $invoiceId,
        $transactionId,
        $amount,
        $fee,
        $gatewayModule
    );
    /**
     * Save log entry to the Gateway Log. Name, data (as array) and status
     */
    logTransaction(
        $GATEWAY["name"],
        $debug,
        "Successful"
    );
} else {
    /**
     * Unsuccessful payment.
     * Save log entry to the Gateway Log. Name, data (as array) and status
     */
    logTransaction(
        $GATEWAY["name"],
        $debug,
        "Unsuccessful"
    );
}

?>
