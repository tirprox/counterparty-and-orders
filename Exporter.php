<?php
require 'vendor/autoload.php';
include "Auth.php";

use GuzzleHttp\Promise;
use GuzzleHttp\Client;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class Exporter {
	const MS_BASE_URL = "https://online.moysklad.ru/api/remap/1.1/entity/";
	const MS_POST_URL = "https://online.moysklad.ru/api/remap/1.1/entity/counterparty/";
	const HEADERS = [
		'auth'           => [ Auth::login, Auth::password ],
		'headers'        => [ 'Content-Type' => 'application/json' ],
		'stream_context' => [
			'ssl' => [
				'allow_self_signed' => true
			],
		],
		'verify'         => false,
	];
	
	var $client, $promises = [];
	
	function __construct() {
		$this->client = new Client( self::HEADERS );
	}
	
	function encodeCounterparty($item) {
		$name = $item->row->clientName;
		$encoded = [
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
		return $encoded;
	}
	
	function exportCounterparty( $counterpartyJSON ) {
		
		
		$this->requestCounterparty($counterpartyJSON);
	}
	
	function requestCounterparty($counterparty) {
		$requestUrl = self::MS_BASE_URL . "counterparty?search=" . prepare_phone($counterparty['phone']);
		$promise = $this->client->requestAsync('GET', $requestUrl,self::HEADERS);
		$promise->then(
			function (ResponseInterface $res) use ($counterparty) {
				$response = json_decode($res->getBody());
				
				if(count($response->rows)===0) {
					print("posting client\n");
					$this->postCounterparty($counterparty);
				}
			},
			function (RequestException $e) {
				echo $e->getMessage() . "\n";
				echo $e->getRequest()->getMethod();
			}
		);
		$this->promises[] = $promise;
	}
	
	function postCounterparty($counterparty) {
		$postJSON = json_encode($counterparty, JSON_UNESCAPED_UNICODE);
		$options = array_merge(self::HEADERS, ['body' => $postJSON]);
		
		$postPromise = $this->client->requestAsync('POST', self::MS_POST_URL, $options);
		$postPromise->then(
			function (ResponseInterface $res){
				$response = json_decode($res->getBody());
			},
			function (RequestException $e) {
				echo $e->getMessage() . "\n";
				echo $e->getRequest()->getMethod();
			});
		$this->promises[] = $postPromise;
	}
	
	function completeAllRequests() {
		Promise\settle($this->promises)->wait();
	}
	
	static function prepare_phone($phone) {
		$phone = str_replace("+", "", $phone);
		$phone = str_replace("-", "", $phone);
		$phone = str_replace(" ", "", $phone);
		return $phone;
	}
	
	static function prepare_time($date, $time) {
		$dateArray = date_parse($date);
		$timeString = $dateArray['year']. "-" . $dateArray['month']. "-" . $dateArray['day'] . " " . $time;
		return $timeString;
	}
}