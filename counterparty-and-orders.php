<?php
/*
Plugin Name: Экспорт контрагентов и заказов
Plugin URI:
Description: Экспорт пользователей и заказов в Мой Склад
Version:     1.0
Author:      Gleb Samsonov
Author URI:  https://developer.wordpress.org/
License:     GPL2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
Text Domain: counterparty-and-orders
Domain Path: /languages
*/
include( plugin_dir_path( __FILE__ ) . 'MSOrderExporter.php');

add_action('woocommerce_payment_complete', 'post_order', 10, 1);
//add_action('woocommerce_thankyou', 'post_order', 10, 1);
function post_order( $order_id ) {

    if ( ! $order_id )
        return;


    // Getting an instance of the order object
    $order = wc_get_order( $order_id );

    $orderExporter = new MSOrderExporter();
    $orderExporter->exportOrder($order);

    if ( $order->user_id > 0 ) {
        update_user_meta( $order->user_id, 'paying_customer', 1 );
        $user = new WP_User( $order->user_id );

        // Remove role
        $user->remove_role( 'customer' );

        // Add role
        $user->add_role( 'customerregistered' );
    }
}