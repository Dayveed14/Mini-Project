<?php

$result = array();
//Set other parameters as keys in the $postdata array
$postdata =  array('email' => 'anthonyegbah@email.com', 'amount' => 700000,"reference" => '7PVGX8MEk85tgeE3pVDtD');
$url = "https://api.paystack.co/transaction/initialize";

$ch = curl_init();
curl_setopt($ch, CURLOPT_URL,$url);
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_POSTFIELDS,json_encode($postdata));  //Post Fields
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

$headers = [
  'Authorization: Bearer sk_test_30fcebdf06aa8647805878e92c01a7f60a1daa1e',
  'Content-Type: application/json',

];
curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);

$request = curl_exec ($ch); 

var_dump($request);
curl_close ($ch);

if ($request) {
  $result = json_decode($request, true);
}

?>