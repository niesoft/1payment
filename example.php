<?php

require_once( dirname(__FILE__) . "/lib/1payment.php" );


$onepayment = new OnePayment("partner_id", "project_id", "api_key");
$result = $onepayment->setData([
	'amount' => 100, 
	'description' => "Test Payment"
])->getForm(debug: true);

var_dump($result);