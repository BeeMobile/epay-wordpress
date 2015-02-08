<?php
/*
	Plugin Name: WooCommerce Kkb Gateway
	Plugin URI: http://woocommerce.com/
	Description: A payment gateway for epay.kkb.kz payment system, Kkb.
	Version: 1.0.0
	Author: Kuanshaliyev Mirzhan
	Author URI: http://woothemes.com/
	Requires at least: 3.5
	Tested up to: 3.8
*/


add_action( 'plugins_loaded', 'woocommerce_kkb_init', 0 );
add_action( 'woocommerce_order_status_changed',  'status_changed', 10, 3);

/**
 * Initialize the gateway.
 */
function woocommerce_kkb_init() {

	if ( ! class_exists( 'WC_Payment_Gateway' ) ) return;

	require_once( plugin_basename( 'classes/kkb.class.php' ) );

	$plugin_dir = basename(dirname(__FILE__)) . '/languages';
	load_plugin_textdomain( 'woocommerce-gateway-kkb', false, $plugin_dir);

	add_filter('woocommerce_payment_gateways', 'woocommerce_kkb_add_gateway' );

}

function status_changed($order_id, $old_status, $new_status)
{
	$order = new WC_Order( $order_id );
	if ($order->payment_method == 'kkb')
	{
		if ( WC()->payment_gateways() ) {
			$payment_gateways = WC()->payment_gateways->payment_gateways();
		}
		if ( isset( $payment_gateways[ $order->payment_method ] ) ) {
			if ($payment_gateways[ $order->payment_method ]->settings['approve_method'] == 'manual')
			{
				if ($old_status == 'on-hold' && $new_status == 'completed')
				{
					$response = get_post_meta($order_id, 'kkb_fullresponse', true);
					$response2 = (array)json_decode($response);
					$payment_gateways[ $order->payment_method ]->approvePayment($response2, $order);
				} else if ($old_status == 'on-hold' && $new_status == 'refunded')
				{
					$response = get_post_meta($order_id, 'kkb_fullresponse', true);
					$response2 = (array)json_decode($response);
					$payment_gateways[ $order->payment_method ]->refundPayment($response2, $order);
				}
			}


		}
	}


}

/**
 * Add the gateway to WooCommerce
 */
function woocommerce_kkb_add_gateway( $methods ) {
	$methods[] = 'WC_Gateway_Kkb';
	return $methods;
}


add_filter( 'woocommerce_currencies', 'add_kzt_currency' );

function add_kzt_currency( $currencies ) {
	$currencies['KZT'] = __( 'Kazakhstan tenge', 'woocommerce' );
	return $currencies;
}

add_filter('woocommerce_currency_symbol', 'add_kzt_currency_symbol', 10, 2);

function add_kzt_currency_symbol( $currency_symbol, $currency ) {
	switch( $currency ) {
		case 'KZT': $currency_symbol = '&#x20b8;'; break;
	}
	return $currency_symbol;
}

