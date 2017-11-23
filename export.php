<?php

require 'vendor/autoload.php';
use MoySklad\MoySklad;
use MoySklad\Entities\Counterparty;
use MoySklad\Lists\EntityList;
$data = json_decode(file_get_contents("data.json"));
//var_dump($data);
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
			]
			
		],
	];
}

$sklad = MoySklad::getInstance(Auth::login, Auth::password);


foreach ($dataToMS as $item) {
	$client = new Counterparty($sklad, $item);
	$counterparties[] = $client;
	$client->create();
}
