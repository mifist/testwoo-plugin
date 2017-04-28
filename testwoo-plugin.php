<?php
/**
 * Plugin Name: TestWoo Plugin
 * Plugin URI: https://github.com/mifist/testwoo-plugin
 * Description: Test of plugin for Woocommerce
 * Version: 1.0.0
 * Author: WooCommerce
 * Author URI: http://woocommerce.com/
 * Developer: Daria Moskalets
 * Developer URI: https://daria-moskalets.in.ua/
 * Text Domain: testwoo-plugin
 * Domain Path: /languages
 *
 * Copyright: © 2009-2015 WooCommerce.
 * License: GNU General Public License v3.0
 * License URI: http://www.gnu.org/licenses/gpl-3.0.html
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

// check activation
if (! in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	function activation(){
		deactivate_plugins('testwoo-plugin/testwoo-plugin.php');
		wp_die('Error wordpress');
	}
	register_activation_hook( __FILE__, 'activation' );
}
if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {
	
	add_action( 'plugins_loaded', 'wc_les_my_init', 0 );
	
	// include custom WC_Integration class
	function wc_les_my_init() {
		require_once dirname(__FILE__) . '/TestWoo_WC_Integration.php';
	}
	
	add_filter( 'woocommerce_integrations', 'add_woocommerce_integrations' );
	function add_woocommerce_integrations( $integrations ) {
		$integrations[] = 'TestWoo_WC_Integration';
		return $integrations;
	}
	
	add_action( 'woocommerce_api_callback', 'callback_handler' );
	function callback_handler(){
		error_log('Run callback');
	}
	
	// Include our Gateway Class and Register Payment Gateway with WooCommerce
	add_action( 'plugins_loaded', 'wc_les_liqpay_init', 0 );
	
	function wc_les_liqpay_init() {
		require_once dirname(__FILE__) . '/WC_LiqPay_Payment_Gateway.php';
	}
	
	add_filter( 'woocommerce_payment_gateways', 'add_liqpay_gateway_class' );
	function add_liqpay_gateway_class( $methods ) {
		//error_log(print_r($methods, true));
		$methods[] = 'WC_LiqPay_Payment_Gateway';
		return $methods;
	}
	
	// Add custom action links
	add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), 'wc_les_liqpay_action_links' );
	function wc_les_liqpay_action_links( $links ) {
		$plugin_links = array(
			'<a href="' . admin_url( 'admin.php?page=wc-settings&tab=checkout' ) . '">'
			. __( 'Settings') . '</a>',
		);
		
		// Merge our new link with the default ones
		return array_merge( $plugin_links, $links );
	}
	
	
	// Создайте функцию для размещения своего класса
	function novaya_pochta_shipping_method_init() {
		require_once dirname(__FILE__) . '/WC_Novaya_Pochta_Shipping_Method.php';
	}
	
	add_action( 'woocommerce_shipping_init', 'novaya_pochta_shipping_method_init' );
	
	
	function add_novaya_pochta_shipping_method( $methods ) {
		$methods['novaya_pochta_shipping_method'] = 'WC_Novaya_Pochta_Shipping_Method';
		return $methods;
	}
	add_filter( 'woocommerce_shipping_methods', 'add_novaya_pochta_shipping_method' );
	
	function novaya_pochta_validate_order( $posted )   {
		
		$packages = WC()->shipping->get_packages();
		
		$chosen_methods = WC()->session->get( 'chosen_shipping_methods' );
		
		if( is_array( $chosen_methods ) && in_array( 'novaya_pochta', $chosen_methods ) ) {
			
			foreach ( $packages as $i => $package ) {
				
				if ( $chosen_methods[ $i ] != "novaya_pochta" ) {
					
					continue;
					
				}
				
				$_Novaya_Pochta_Shipping_Method = new WC_Novaya_Pochta_Shipping_Method();
				$weightLimit = (int) $_Novaya_Pochta_Shipping_Method->settings['weight'];
				$weight = 0;
				
				foreach ( $package['contents'] as $item_id => $values )
				{
					$_product = $values['data'];
					$weight = $weight + $_product->get_weight() * $values['quantity'];
				}
				
				$weight = wc_get_weight( $weight, 'kg' );
				
				if( $weight > $weightLimit ) {
					
					$message = sprintf( __( 'Sorry, %d kg exceeds the maximum weight of %d kg for %s', 'tutsplus' ), $weight, $weightLimit, $_Novaya_Pochta_Shipping_Method->title );
					
					$messageType = "error";
					
					if( ! wc_has_notice( $message, $messageType ) ) {
						
						wc_add_notice( $message, $messageType );
						
					}
				}
			}
		}
	}
	
	add_action( 'woocommerce_review_order_before_cart_contents', 'novaya_pochta_validate_order' , 10 );
	add_action( 'woocommerce_after_checkout_validation', 'novaya_pochta_validate_order' , 10 );
	
	
} else {
	
	add_action( 'admin_notices', 'maybe_display_admin_notices'  );
	function maybe_display_admin_notices () {
		echo '<div class="error fade"><p>Error woocommerce</p></div>' . "\n";
	}
	
}