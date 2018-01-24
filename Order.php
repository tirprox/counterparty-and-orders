<?php
/**
 * Created by PhpStorm.
 * User: dreamwhite
 * Date: 22.01.2018
 * Time: 16:51
 */

class Order
{
    public $counterparty, $products = [];
    var $name;
    var $id;

    function addProduct(string $name, string $sku, int $quantity) : void {
        $this->products[] = [
            'name' => $name,
            'sku' => $sku,
            'quantity' => $quantity
        ];

    }

    function encodeForMS() : string {
        $data = [
            'name' => (string) $this->name,
            "organization" => [
                "meta" => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/organization/8f3fb0c0-e00e-11e6-7a69-9711001f668a",
                    "type" => "organization",
                    "mediaType" => "application/json"
                ]
            ],
            'agent' => [
                'meta' => [
                    'href' => "https://online.moysklad.ru/api/remap/1.1/entity/counterparty/" . $this->counterparty->id,
                    "type" => "counterparty",
                    "mediaType" => "application/json"
                ]
            ]
        ];
        return json_encode($data, JSON_UNESCAPED_UNICODE);
    }

}