<?php
/**
 * Created by PhpStorm.
 * User: dreamwhite
 * Date: 22.01.2018
 * Time: 12:48
 */
require_once "Order.php";
require_once "MSExporter.php";
require_once "Counterparty.php";

class MSOrderExporter
{

    const MS_BASE_URL = "https://online.moysklad.ru/api/remap/1.1/entity/";
    const MS_POST_COUNTERPARTY_URL = "https://online.moysklad.ru/api/remap/1.1/entity/counterparty/";
    const MS_POST_ORDER_URL = "https://online.moysklad.ru/api/remap/1.1/entity/customerorder/";

    var $exporter, $client;
    function __construct()
    {
        $this->exporter = new MSExporter();
        $this->client = $this->exporter->client;
    }


    function exportOrder(WC_Order $wcOrder) {
        $order = new Order();

        $order_data = $wcOrder->get_data();
        $order->name = $order_data['id'];
        $phone =  $order_data['billing']['phone'];
        $email = $order_data['billing']['email'];
        $name = $order_data['billing']['first_name'];

        $counterparty = new Counterparty($name, $phone, $email);
        $counterparty->parseJson($this->requestCounterparty($counterparty));

        //$order->counterparty = $this->requestCounterparty($counterparty);
        $order->counterparty = $counterparty;

        $order = $this->addProductsToOrder($wcOrder, $order);
        $order = $this->postOrder($order);

        foreach ($order->products as $product) {

            $data = $this->getProductBySku($product['sku']);
            $this->postProductToOrder($data, $order, $product);

        }


    }

    // if counterparty exists in MS, getting its json, else post new counterparty to MS and return its json
    function requestCounterparty(Counterparty $counterparty) {
        $request = $this->getCounterpartyByPhone($counterparty->phone);

        if ($request == null) {
            $response = $this->postCounterparty($counterparty);
        }
        else {
            $response = $request;
        }

        return $response;
    }

    function postOrder(Order $order) : Order {
        $data = $order->encodeForMS();
        $options = array_merge(MSExporter::HEADERS, ['body' => $order->encodeForMS()]);

        $response = $this->client->post(self::MS_POST_ORDER_URL, $options);
        $orderData = json_decode($response->getBody());
        $order->id = $orderData->id;
        return $order;
        //var_dump(json_decode($response->getBody()));
    }

    function getProductBySku(string $sku) : stdClass {
        $requestUrl = self::MS_BASE_URL . "product?filter=code=" . $sku;
        $response = $this->client->get($requestUrl);
        $response = json_decode($response->getBody());
        return $response->rows[0];

    }

    function postProductToOrder(stdClass $MSproduct, Order $order, array $orderProduct) {
        $requestUrl = self::MS_BASE_URL . "customerorder/$order->id/positions";

        $data = [
            'quantity' => (int) $orderProduct['quantity'],
            "assortment" => [
                "meta" => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/product/$MSproduct->id",
                    "type" => "variant",
                    "mediaType" => "application/json"
                ]
            ]
        ];

        $postJSON = json_encode($data, JSON_UNESCAPED_UNICODE);
        $options = array_merge(MSExporter::HEADERS, ['body' => $postJSON]);

        $response = $this->client->post( $requestUrl, $options);
        return json_decode($response->getBody());
    }

    function addProductsToOrder(WC_Order $wcOrder, Order $order) : Order {
        $order_items = $wcOrder->get_items();
        foreach( $order_items as $item ) {

            $product = wc_get_product( $item['variation_id'] );
            //var_dump($product->get_sku());
            $order->addProduct($item['name'], $product->get_sku(), $item['qty']);

        }

        return $order;
    }

    function postCounterparty(Counterparty $counterparty) {

        $postJSON = $counterparty->encodeForMS();
        $options = array_merge(MSExporter::HEADERS, ['body' => $postJSON]);

        $response = $this->client->post(self::MS_POST_COUNTERPARTY_URL, $options);
        return json_decode($response->getBody());
    }


    function getCounterpartyByPhone($phone) {
        $requestUrl = self::MS_BASE_URL . "counterparty?search=" . $phone;
        $response = $this->client->get($requestUrl);
        $response = json_decode($response->getBody());
        if(count($response->rows)===0) {
            return null;
        }
        else {
            return $response;
        }

    }
}