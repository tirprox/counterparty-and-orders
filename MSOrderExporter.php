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
require_once "MSLogger.php";

ini_set("log_errors", 1);
ini_set("error_log", "errors.log");
//error_log( "Hello, errors!" );

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

    function exportCounterparty(WC_Order $wcOrder) {
        $order = new Order();

        $order_data = $wcOrder->get_data();
        $order->name = $order_data['id'];

        $order->wc_order_data = $order_data;
        $shippingPrice = $wcOrder->get_total_shipping();

        $phone =  $order_data['billing']['phone'];
        $email = $order_data['billing']['email'];
        $name = $order_data['billing']['first_name'];


        $lastname = $order_data['billing']['last_name'];

        $country = WC()->countries->countries[$order_data['billing']['country']];
        //$country = $order_data['billing']['country'];
        $city = $order_data['billing']['city'];
        $address = $order_data['billing']['address_1'];
        $postcode = $order_data['billing']['postcode'];




        $counterparty = new Counterparty($name, $phone, $email);
        $counterparty->addLastName($lastname);


        $counterparty->addCountry($country);
        $counterparty->addCity($city);
        $counterparty->addAddress($address);
        $counterparty->addPostcode($postcode);


        $counterparty->parseJson($this->requestCounterparty($counterparty));



    }


    function exportOrder(WC_Order $wcOrder) {
        $order = new Order();

        $order_data = $wcOrder->get_data();
        $order->name = $order_data['id'];

        $order->wc_order_data = $order_data;
        $shippingPrice = $wcOrder->get_total_shipping();

        $phone =  $order_data['billing']['phone'];
        $email = $order_data['billing']['email'];
        $name = $order_data['billing']['first_name'];


        $lastname = $order_data['billing']['last_name'];

        $country = WC()->countries->countries[$order_data['billing']['country']];
        //$country = $order_data['billing']['country'];
        $city = $order_data['billing']['city'];
        $address = $order_data['billing']['address_1'];
        $postcode = $order_data['billing']['postcode'];




        $counterparty = new Counterparty($name, $phone, $email);
        $counterparty->addLastName($lastname);


        $counterparty->addCountry($country);
        $counterparty->addCity($city);
        $counterparty->addAddress($address);
        $counterparty->addPostcode($postcode);

        $counterparty->parseJson($this->requestCounterparty($counterparty));


        //$order->counterparty = $this->requestCounterparty($counterparty);
        $order->counterparty = $counterparty;

        $order = $this->addProductsToOrder($wcOrder, $order);
        $order = $this->postOrder($order);

        foreach ($order->products as $product) {

//            $data = $this->getProductBySku($product['sku']);

            //$variantName = $product['name']. ' (' . $product['color'] . ', ' . $product['size'] . ')';
            //MSLogger::log("varname: " . $variantName);
            //$data = $this->getProductVariantByName($variantName);
            $this->postProductToOrder($order, $product);

        }



        $this->postDeliveryToOrder($order,  $shippingPrice);

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
        //error_log( print_r( $response , true ) );
        $orderData = json_decode($response->getBody());
        $order->id = $orderData->id;
        return $order;
        //var_dump(json_decode($response->getBody()));
    }

    function getProductBySku(string $sku) : stdClass {
        // filter variant by sku is not working!!!
        $requestUrl = self::MS_BASE_URL . "product?filter=code=" . $sku;

        $response = $this->client->get($requestUrl);
        $response = json_decode($response->getBody());
        return $response->rows[0];

    }

    function getProductVariantByName(string $name) : stdClass {
        // filter variant by sku is not working!!!
        $requestUrl = self::MS_BASE_URL . "variant?filter=name=" . $name;
        print($requestUrl);

        $response = $this->client->get($requestUrl);
        $response = json_decode($response->getBody());
        return $response->rows[0];

    }

    function postDeliveryToOrder(Order $order, $price) {
        $requestUrl = self::MS_BASE_URL . "customerorder/$order->id/positions";
        echo $requestUrl;
        $data = [
            'quantity' => 1,
            'price' => $price * 100,
            "assortment" => [
                "meta" => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/service/08b5fb81-b0f3-11e8-9ff4-34e80004e692", // Доставка 1000
                    //"href" => "https://online.moysklad.ru/api/remap/1.1/entity/service/c7788cc1-30dc-11e8-9109-f8fc00115919", // Доставка 500
                    "type" => "service",
                    "mediaType" => "application/json"
                ]
            ]
        ];

        $postJSON = json_encode($data, JSON_UNESCAPED_UNICODE);
        $options = array_merge(MSExporter::HEADERS, ['body' => $postJSON]);

        $response = $this->client->post( $requestUrl, $options);
        //var_dump(json_decode($response->getBody()));
        return json_decode($response->getBody());
    }

    function postProductToOrder(Order $order, array $orderProduct) {
        $requestUrl = self::MS_BASE_URL . "customerorder/$order->id/positions";

        $data = [
            'quantity' => (int) $orderProduct['quantity'],
            'price' => $this->getPrice($orderProduct['price']),
            "assortment" => [
                "meta" => [
                    "href" => "https://online.moysklad.ru/api/remap/1.1/entity/product/" . $orderProduct['id'],
                    "type" => "variant",
                    "mediaType" => "application/json"
                ]
            ],

        ];

        $postJSON = json_encode($data, JSON_UNESCAPED_UNICODE);
        $options = array_merge(MSExporter::HEADERS, ['body' => $postJSON]);

        $response = $this->client->post( $requestUrl, $options);
        //var_dump(json_decode($response->getBody()));
        return json_decode($response->getBody());
    }

    function getPrice($productPrice){
        $price = (int) $productPrice;
        $price = $price * 100;
        return $price;
    }

    function addProductsToOrder(WC_Order $wcOrder, Order $order) : Order {
        $order_items = $wcOrder->get_items();
        foreach( $order_items as $item ) {

            $product = wc_get_product( $item['product_id'] );
            $variant = wc_get_product( $item['variation_id'] );

            //$product = wc_get_product( $item['variation_id'] );
            $name = $product->get_name();
            $color = $variant->get_attribute('pa_tsvet');
            $size = $variant->get_attribute('pa_razmer');
            $price = $item->get_total();
            //$price = "10000";

            $sku = get_post_meta( $item['variation_id'], '_sku', true );
            $ms_id = get_post_meta( $item['variation_id'], '_ms_id', true );
            MSLogger::log("sku from wc: " . $sku);
            MSLogger::log("name: " . $name);
            //echo $ms_id;
//            $sku = "0005824";

            $order->addProduct($name, $sku, $item['qty'], $color, $size, $price, $ms_id);
            //$order->addProduct($item['name'], /*$product->get_sku()*/ $sku, $item['qty']);

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
            return $response->rows[0];
        }

    }


}