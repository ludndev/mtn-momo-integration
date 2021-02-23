<?php


require 'vendor/autoload.php';


use Ramsey\Uuid\Uuid;

function line($value) {
	echo "<b>" . $value . "<b><br/><hr><br/>";
}



$uuid = Uuid::uuid4();

$client = new \GuzzleHttp\Client([
	'base_uri' => 'https://sandbox.momodeveloper.mtn.com/',
	'verify' => true // due to https://github.com/guzzle/guzzle/issues/1935 ( only on sandbox ? )
]);

$env = 'sandbox'; //X-Target-Environment
$ocp = '9aa23a3a28bc46ea96004885db8a81c5'; //Ocp-Apim-Subscription-Key
$callback = 'localhost';



try {
	
	# Sandbox User Provisioning
	line("Sandbox User Provisioning");
	
	$refid = $uuid->toString();

	$options = [
	    'headers' => [
	    	'Accept' 					=> 'application/json',
	        'X-Reference-Id' 			=> $refid,
	        'Ocp-Apim-Subscription-Key'	=> $ocp,
	        'X-Target-Environment'      => $env
	    ],
	    'body' => json_encode([
	    	'providerCallbackHost' => $callback
	    ])
	];

	$response = $client->request(
		'POST', 
		'v1_0/apiuser', // https://sandbox.momodeveloper.mtn.com/v1_0/apiuser
		$options
	);

	if (isset($_GET['debug'])) {
		//echo $response->getStatusCode(); echo "<br>"; // 201
		$body = json_decode($response->getBody());
		echo "<pre>"; print_r($body); echo "</pre>";
	}

	/*******************************/

	# Sandbox API KEY Provisioning
	line("Sandbox API KEY Provisioning");
	
	$options = [
	    'headers' => [
	    	'Accept' 					=> 'application/json',
	        'X-Reference-Id' 			=> $refid,
	        'Ocp-Apim-Subscription-Key'	=> $ocp,
	        'X-Target-Environment'      => $env
	    ],
	    'body' => json_encode([
	    	'providerCallbackHost' => $callback
	    ])
	];

	$response = $client->request(
		'POST', 
		"v1_0/apiuser/$refid/apikey", // https://sandbox.momodeveloper.mtn.com/v1_0/apiuser/{X-Reference-Id}/apikey
		$options
	);

	if (isset($_GET['debug'])) {
		//echo $response->getStatusCode(); echo "<br>"; // 201
		$body = json_decode($response->getBody());
		echo "<pre>"; print_r($body); echo "</pre>";
	}

	$apikey = json_decode($response->getBody())->apiKey; // get api key

	/*******************************/

	# Collection (token)
	line("Collection (token)");
	
	$auth = base64_encode("$refid:$apikey");
	
	$options = [
	    'headers' => [
	    	'Authorization' 			=> "Basic $auth",
	        'Ocp-Apim-Subscription-Key'	=> $ocp
	    ]
	];

	$response = $client->request(
		'POST', 
		"collection/token/", // https://sandbox.momodeveloper.mtn.com/collection/token/
		$options
	);

	if (isset($_GET['debug'])) {
		//echo $response->getStatusCode(); echo "<br>"; // 201
		$body = json_decode($response->getBody());
		echo "<pre>"; print_r($body); echo "</pre>";
	}


	$body = json_decode($response->getBody()); 

	//echo "<pre>"; print_r($body); echo "</pre>";

	$accesstoken = $body->access_token;


	/*******************************/

	# Collection (request to pay)
	line("Collection (request to pay)");
	
	$options = [
	    'headers' => [
	    	'Authorization' 			=> "Bearer $accesstoken",
	    	//'X-Callback-Url' 			=> $callback, # not allowed on sandbox
	    	'X-Reference-Id' 			=> $refid,
	    	'X-Target-Environment' 		=> $env,
	    	'Content-Type' 				=> 'application/json',
	        'Ocp-Apim-Subscription-Key'	=> $ocp
	    ],
	    'body' => json_encode([
	    	'amount' => '1000',
	    	'currency' => 'EUR',
	    	'externalId' => (string)time(),
	    	'payer' => [
	    		'partyIdType' => 'MSISDN',
	    		'partyId' => '22962529171'
	    	],
	    	'payerMessage' => 'Lorem Ipsum',
	    	'payeeNote' => 'TESTING'
	    ])
	];

	$response = $client->request(
		'POST', 
		"collection/v1_0/requesttopay", // https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay
		$options
	);

	if (isset($_GET['debug'])) {
		//echo $response->getStatusCode(); echo "<br>"; // 201
		$body = json_decode($response->getBody());
		echo "<pre>"; print_r($body); echo "</pre>";
	}

	/*******************************/

	# Collection (check request)
	line("Collection (check request)");

	$options = [
	    'headers' => [
	    	'Authorization' 			=> "Bearer $accesstoken",
	    	'X-Target-Environment' 		=> $env,
	    	'Content-Type' 				=> 'application/json',
	        'Ocp-Apim-Subscription-Key'	=> $ocp
	    ]
	];

	$response = $client->request(
		'GET', 
		"/collection/v1_0/requesttopay/$refid", // https://sandbox.momodeveloper.mtn.com/collection/v1_0/requesttopay/{referenceId}
		$options
	);

	if (isset($_GET['debug'])) {
		//echo $response->getStatusCode(); echo "<br>"; // 201
		$body = json_decode($response->getBody());
		echo "<pre>"; print_r($body); echo "</pre>";
	}




	line("RESUME");

	echo "Everything is OK !";
	echo "<hr>";

	echo "REF_ID : $refid  <br>";
	echo "API_KEY : $apikey  <br>";
	echo "BASIC_AUTH : $auth  <br>";
	echo "ACCESS_TOKEN : $accesstoken  <br>";





} catch (Exception $e) {

	echo "\r\n" .$e->getMessage();
	echo "<hr>";

	echo "REF_ID : $refid  <br>";
	echo "API_KEY : $apikey  <br>";
	echo "BASIC_AUTH : $auth  <br>";
	echo "ACCESS_TOKEN : $accesstoken  <br>";
	
}

