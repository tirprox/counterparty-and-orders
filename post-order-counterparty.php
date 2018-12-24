<?php
ini_set('display_startup_errors', 1);
ini_set('display_errors', 1);
error_reporting(-1);

require_once( 'MSOrderExporter.php');
//define( 'SHORTINIT', true );
require_once (dirname(__DIR__, 3) . '/wp-blog-header.php');

if (isset( $_GET['id'])) {


    $order = wc_get_order( $_GET['id'] );
    //var_dump($order);
    echo $_GET['id'];


    $orderExporter = new MSOrderExporter();
    $orderExporter->exportCounterparty($order);
}


