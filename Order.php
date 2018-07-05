<?php


class Order
{
    public $counterparty, $products = [];
    var $name;
    var $id;
    var $data = [];
    var $wc_order_data = [];


    var $stores = [
        "Флигель" => "baedb9ed-de2a-11e6-7a34-5acf00087a3f",
        "В белом" => "4488e436-07e7-11e6-7a69-971100273f23",
        "Флигель Спб" => "83351169-8038-11e8-9ff4-34e800057d4a",
    ];

    function __construct()
    {
        $this->setStore('Флигель Спб');


    }

    function addProduct(string $name, string $sku, int $quantity, string $color, string $size, string $price = '0', $id): void
    {
        $this->products[] = [
            'id' => $id,
            'name' => $name,
            'color' => $color,
            'size' => $size,
            'sku' => $sku,
            'quantity' => $quantity,
            'price' => $price
        ];

    }

    function encodeForMS(): string
    {
        $this->data['name'] = (string)$this->name;
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

        $this->data['group'] = [
            "meta" => [
                "href" => "https://online.moysklad.ru/api/remap/1.1/entity/group/59c74466-a4ef-11e7-7a69-8f5500021289",
                "metadataHref" => "https://online.moysklad.ru/api/remap/1.1/entity/group/metadata",
                "type" => "group",
                "mediaType" => "application/json"
            ]
        ];

        $phone =  $this->wc_order_data['billing']['phone'];
        $email = $this->wc_order_data['billing']['email'];
        $name = $this->wc_order_data['billing']['first_name'];
        $lastname = $this->wc_order_data['billing']['last_name'];

        $country = WC()->countries->countries[$this->wc_order_data['billing']['country']];
        $city = $this->wc_order_data['billing']['city'];
        $address = $this->wc_order_data['billing']['address_1'];
        $postcode = $this->wc_order_data['billing']['postcode'];


        $this->data['attributes'] = [
            [
                'id' => '1384ed98-3da3-11e8-9107-5048000c53cf',
                'name' => 'Город доставки',
                'type' => 'string',
                'value' => $city,
            ],
            [
                'id' => '1384f0bb-3da3-11e8-9107-5048000c53d0',
                'name' => 'Телефон для доставки',
                'type' => 'string',
                'value' => $phone,
            ],
            [
                'id' => '1384f372-3da3-11e8-9107-5048000c53d1',
                'name' => 'Предоплата при доставке',
                'type' => 'boolean',
                'value' => true,
            ],
            [
                'id' => '1384f9c8-3da3-11e8-9107-5048000c53d3',
                'name' => 'Адрес доставки',
                'type' => 'string',
                'value' => $address,
            ],
            /*[
                'id' => '1384fc21-3da3-11e8-9107-5048000c53d4',
                'name' => 'Комментарий к доставке',
                'type' => 'string',
                'value' => 'asdassd',
            ],*/
            [
                'id' => '1385006e-3da3-11e8-9107-5048000c53d6',
                'name' => 'Метод оплаты',
                'type' => 'string',
                'value' => 'Предоплата',
            ],
            [
                'id' => '1385025e-3da3-11e8-9107-5048000c53d7',
                'name' => 'Контактное лицо для доставки',
                'type' => 'string',
                'value' => $name . " " . $lastname,
            ],
            [
                'id' => '13850780-3da3-11e8-9107-5048000c53d9',
                'name' => 'Тарифы доставки СДЭК',
                'type' => 'customentity',
                'value' =>
                    [
                        'meta' =>
                            [
                                'href' => 'https://online.moysklad.ru/api/remap/1.1/entity/customentity/1622bee3-3da2-11e8-9ff4-34e8000c0bab/43c4a312-3da2-11e8-9ff4-34e8000be921',
                                'metadataHref' => 'https://online.moysklad.ru/api/remap/1.1/entity/companysettings/metadata/customEntities/1622bee3-3da2-11e8-9ff4-34e8000c0bab',
                                'type' => 'customentity',
                                'mediaType' => 'application/json',
                                'uuidHref' => 'https://online.moysklad.ru/app/#custom_1622bee3-3da2-11e8-9ff4-34e8000c0bab/edit?id=43c4a312-3da2-11e8-9ff4-34e8000be921',
                            ],
                        'name' => 'Посылка склад-дверь',
                    ],
            ],
            [
                'id' => '7595c443-3da4-11e8-9107-5048000c64ac',
                'name' => 'Страховка',
                'type' => 'boolean',
                'value' => true,
            ],
        ];

        return json_encode($this->data, JSON_UNESCAPED_UNICODE);
    }

    function setStore(string $store): void
    {
        $this->data['store'] = [
            'meta' => [
                "href" => "https://online.moysklad.ru/api/remap/1.1/entity/store/" . $this->stores[$store],
                "type" => "store",
                "mediaType" => "application/json"
            ]
        ];
    }

}