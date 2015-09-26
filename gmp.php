<?php

require_once('gmp/mercadopago.php');

function gmp_config() {
    $configarray = array(
     "FriendlyName" => array("Type" => "System", "Value"=>"WHMCS MercadoPago"),
     "client_id" => array("FriendlyName" => "Client ID", "Type" => "text", "Size" => "30", ),
     "client_secret" => array("FriendlyName" => "Client Secret", "Type" => "text", "Size" => "60", ),
     "testmode" => array("FriendlyName" => "Test Mode", "Type" => "yesno", "Description" => "Tick this to test", ),
    );
	return $configarray;
}

function gmp_link($params) {

  $clientId = $params['client_id'];
  $clientSecret = $params['client_secret'];
  $testmode = $params['testmode'];

	$invoiceid = $params['invoiceid'];
	$description = $params["description"];
  $amount = $params['amount']; # Format: ##.##
  $currency = $params['currency']; # Currency Code

  $mp = new MP($clientId, $clientSecret);
  $preference_data = array(
    "external_reference" => $invoiceid,
  	"items" => array(
  		array(
  			"title" => $description,
  			"quantity" => 1,
  			"currency_id" => $currency, // Available currencies at: https://api.mercadopago.com/currencies
  			"unit_price" => (float)$amount,
  		)
  	)
  );
  $preference = $mp->create_preference($preference_data);
  $initPoint = $testmode ? $preference['response']['sandbox_init_point'] : $preference['response']['init_point'];

  $code = '<a href="'.$initPoint.'">Pagar</a>';

	return $code;
}

?>
