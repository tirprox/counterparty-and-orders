<?php


class Order
{
    public $counterparty, $products = [];
    var $name;
    var $id;
    var $data = [];

    var $stores = [
        "Флигель" => "baedb9ed-de2a-11e6-7a34-5acf00087a3f",
        "В белом" => "4488e436-07e7-11e6-7a69-971100273f23"
    ];

    function __construct() {
        $this->setStore('Флигель');


    }

    function addProduct(string $name, string $sku, int $quantity): void
    {
        $this->products[] = [
            'name' => $name,
            'sku' => $sku,
            'quantity' => $quantity
        ];

    }

    function encodeForMS(): string
    {
       /* $data = [
            'name' => (string)$this->name,
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
            ],
            'store' => [
                'meta' => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/store/baedb9ed-de2a-11e6-7a34-5acf00087a3f",
                    "type" => "store",
                    "mediaType" => "application/json"
                ]
            ]
        ];*/

        $this->data['name'] = (string) $this->name;
        $this->data['organization'] = [
            "meta" => [
                "href" => "https://online.moysklad.ru/api/remap/1.1/entity/organization/8f3fb0c0-e00e-11e6-7a69-9711001f668a",
                "type" => "organization",
                "mediaType" => "application/json"
            ]
        ];
        $this->data['agent'] = [
            'meta' => [
                'href' => "https://online.moysklad.ru/api/remap/1.1/entity/counterparty/" . $this->counterparty->id,
                "type" => "counterparty",
                "mediaType" => "application/json"
            ]
        ];
        /*$this->data['store'] = [
            'meta' => [
                "href" => "https://online.moysklad.ru/api/remap/1.1/entity/store/baedb9ed-de2a-11e6-7a34-5acf00087a3f",
                "type" => "store",
                "mediaType" => "application/json"
            ]
        ];*/


        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }

    function setStore(string $store) : void {
        $this->data['store'] = [
            'meta' => [
                "href" => "https://online.moysklad.ru/api/remap/1.1/entity/store/" . $this->stores[$store],
                "type" => "store",
                "mediaType" => "application/json"
            ]
        ];
    }

}