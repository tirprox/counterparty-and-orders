<?php

require 'vendor/autoload.php';
include "MSAuth.php";

use GuzzleHttp\Promise;
use GuzzleHttp\Client;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

$data = json_decode(file_get_contents("data.json"));

$baseUrl = "https://online.moysklad.ru/api/remap/1.1/entity/";

$dataToMS = [];
foreach ($data as $item) {
	$name = $item->row->clientName;
	$dataToMS[] = [
		"name" => $name,
		"phone" => $item->row->phone,
		"email" => $item->row->email,
		"tags" => ["anketa"],
		"companyType" => "individual",
		"attributes" => [
			[
				"id" =>  "c6597688-cf9b-11e7-7a6c-d2a9000ec13c",
				"name" => "Фамилия",
				"type" =>  "string",
				"value" => $item->row->clientLastName
			],
			[
				"id" => "fe06e4f2-d034-11e7-7a34-5acf0006a4c2",
				"name" => "Источник",
				"type" => "string",
				"value" => $item->row->infoSource
			],
			[
				"id" => "fe06e948-d034-11e7-7a34-5acf0006a4c3",
				"name" => "Дата регистрации анкеты",
				"type" => "time",
				"value" => prepare_time($item->row->date, $item->row->time)
			],
			[
				"id" => "b3d9786a-d361-11e7-7a6c-d2a9001aff01",
				"name" => "Отзыв",
				"type" => "text",
				"value" => $item->row->feedback
			]
		],
	];
}
$headers = [
	'MSAuth' => [MSAuth::login, MSAuth::password],
	'headers'  => ['Content-Type' => 'application/json'],
	'stream_context' => [
		'ssl' => [
			'allow_self_signed' => true
		],
	],
	'verify'         => false,
];

$client = new Client($headers);

$promises = [];


$postUrl = $baseUrl . "counterparty/";
foreach ($dataToMS as $counterparty){
	$requestUrl = $baseUrl . "counterparty?search=" . prepare_phone($counterparty['phone']);
	
	$postJSON = json_encode($counterparty, JSON_UNESCAPED_UNICODE);
	$headerJSON = json_encode($headers, JSON_UNESCAPED_UNICODE);
	
	$promise = $client->requestAsync('GET', $requestUrl, $headers);
	
	$options = array_merge($headers, ['body' => $postJSON]);
	
	
	$promise->then(
		function (ResponseInterface $res) use ($promises, $client, $postUrl, $options){
			$response = json_decode($res->getBody());
         //var_dump(count($response->rows));
			if(count($response->rows)===0) {
			   print("posting client\n");
				$postPromise = $client->requestAsync('POST', $postUrl, $options);
				$postPromise->then(
					function (ResponseInterface $res){
						$response = json_decode($res->getBody());
					},
					function (RequestException $e) {
						echo $e->getMessage() . "\n";
						echo $e->getRequest()->getMethod();
					});
				$promises[] = $postPromise;
			}
			//var_dump($response);
		},
		function (RequestException $e) {
			echo $e->getMessage() . "\n";
			echo $e->getRequest()->getMethod();
		}
	);
	
	$promises[] = $promise;
}

Promise\settle($promises)->wait();

function prepare_phone($phone) {
   $phone = str_replace("+", "", $phone);
   $phone = str_replace("-", "", $phone);
   $phone = str_replace(" ", "", $phone);
   var_dump($phone);
   return $phone;
}

function prepare_time($date, $time) {
   $dateArray = date_parse($date);
   $timeString = $dateArray['year']. "-" . $dateArray['month']. "-" . $dateArray['day'] . " " . $time;
   return $timeString;
}