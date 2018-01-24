<?php
require 'vendor/autoload.php';
include_once "MSAuth.php";

use GuzzleHttp\Promise;
use GuzzleHttp\Client;

use Psr\Http\Message\ResponseInterface;
use GuzzleHttp\Exception\RequestException;

class MSExporter {
	const MS_BASE_URL = "https://online.moysklad.ru/api/remap/1.1/entity/";
	const MS_POST_URL = "https://online.moysklad.ru/api/remap/1.1/entity/counterparty/";
	const HEADERS = [
		'auth' => [ MSAuth::login, MSAuth::password ],
		'headers'        => [ 'Content-Type' => 'application/json' ],
		'stream_context' => [
			'ssl' => [
				'allow_self_signed' => true
			],
		],
		'verify'         => false,
	];
	
	var $client, $promises;
	
	function __construct() {
		$this->client = new Client( self::HEADERS );
      $this->promises = [];
	}

    function encodeOldAnketaItem($item) {
        $name = $item[0];
        $encoded = [
            "name" => $name,
            "phone" => $item[3],
            "email" => $item[4],
            "tags" => ["anketa-paper"],
            "companyType" => "individual",
            "attributes" => [
                [
                    "id" =>  "c6597688-cf9b-11e7-7a6c-d2a9000ec13c",
                    "name" => "Фамилия",
                    "type" =>  "string",
                    "value" => $item[1]
                ],
                [
                    "id" => "fe06e4f2-d034-11e7-7a34-5acf0006a4c2",
                    "name" => "Источник",
                    "type" => "string",
                    "value" => $item[2]
                ],
                [
                    "id" => "fe06e948-d034-11e7-7a34-5acf0006a4c3",
                    "name" => "Дата регистрации анкеты",
                    "type" => "time",
                    "value" => self::prepare_time($item[8], $item[7])
                ],
                [
                    "id" => "f7acf60c-fc67-11e7-7a31-d0fd000e939f",
                    "name" => "Facebook",
                    "type" => "string",
                    "value" => $item[9]
                ],

            ],
        ];
        return $encoded;
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
					"value" => self::prepare_time($item->row->date, $item->row->time)
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

	function exportOldAnketaItem($item) {


        $requestUrl = self::MS_BASE_URL . "counterparty?search=" . self::prepare_phone($item[3]);
        print("url: $requestUrl \n");
        print("name: $item[0] \n");
        print("surname: $item[1] \n");
        print("source: $item[2] \n");
        print("phone: $item[3] \n");
        print("email: $item[4] \n");
        print("date: " . self::prepare_time($item[8], $item[7]) . "\n");

        $encoded = $this->encodeOldAnketaItem($item);
        //$response = $this->client->request('GET', $requestUrl,self::HEADERS);

        $response = $this->client->get($requestUrl);
        $response = json_decode($response->getBody());
        if(count($response->rows)===0) {
            $postJSON = json_encode($encoded, JSON_UNESCAPED_UNICODE);
            $options = array_merge(self::HEADERS, ['body' => $postJSON]);

            $this->client->request('POST', self::MS_POST_URL, $options);
        }

    }

	function exportCounterpartyFromAnketaJSON( $counterpartyJSON ) {
		$this->requestCounterparty($this->encodeCounterparty($counterpartyJSON));
	}
	
	function requestCounterparty($counterparty) {
		$requestUrl = self::MS_BASE_URL . "counterparty?search=" . self::prepare_phone($counterparty['phone']);
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
   
   function checkModel( $model) {
      if ( $model->row != null ) {
         $validatedSum = 0;
         $properties = [
            "clientName",
            "clientLastName",
            "infoSource",
            "isCustomInfoSource",
            "infoSourceCustom",
            "phone",
            "email",
            "feedback",
            "date",
            "time"
         ];
         
         $fieldCount   = count($properties);
         foreach ($properties as $property)
            if (property_exists( $model->row, $property) ) {
               $validatedSum++;
            }
         if ( $validatedSum == $fieldCount ) {
            return true;
         }
      }
      return false;
   }
	
	static function prepare_phone($phone) {
		$phone = str_replace("+", "", $phone);
		$phone = str_replace("-", "", $phone);
		$phone = str_replace(" ", "", $phone);
		return $phone;
	}
	
	static function prepare_time($date, $time) {
        $dateArray = date_parse_from_format("j.n.Y", $date);
		//$dateArray = date_parse($date);
		$timeString = $dateArray['year']. "-" . $dateArray['month']. "-" . $dateArray['day'] . " " . $time;
		return $timeString;
	}
}