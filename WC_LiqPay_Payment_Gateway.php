<?php

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( ! class_exists( 'WC_LiqPay_Payment_Gateway' ) ) {
    class WC_LiqPay_Payment_Gateway extends WC_Payment_Gateway
    {
        // Setup our Gateway's id, description and other values
        public function __construct()
        {

            // The global ID for this Payment method
            $this->id = "liqpay_payment_gateway";

            // The Title shown on the top of the Payment Gateways Page next to all the other Payment Gateways
            $this->method_title = __("LiqPay");

            // The description for this Payment Gateway, shown on the actual Payment options page on the backend
            $this->method_description = __("LiqPay Payment Gateway Plug-in for WooCommerce");

            // The title to be used for the vertical tabs that can be ordered top to bottom
            $this->title = __("LiqPay");

            // If you want to show an image next to the gateway's name on the frontend, enter a URL to an image.
            $this->icon = null;

            // Bool. Can be set to true if you want payment fields to show on the checkout
            // if doing a direct integration, which we are doing in this case
            $this->has_fields = true;

            // Supports the default credit card form
            $this->supports = array('default_credit_card_form');

            // This basically defines your settings which are then loaded with init_settings()
            $this->init_form_fields();

            // After init_settings() is called, you can get the settings and load them into variables, e.g:
            // $this->title = $this->get_option( 'title' );
            $this->init_settings();

            // Turn these settings into variables we can use
            foreach ($this->settings as $setting_key => $value) {
                $this->$setting_key = $value;
            }

            // Lets check for SSL
            add_action('admin_notices', array($this, 'do_ssl_check'));

            // Save settings
            if (is_admin()) {
                // Versions over 2.0
                // Save our administration options. Since we are not going to be doing anything special
                // we have not defined 'process_admin_options' in this class so the method in the parent
                // class will be used instead
                add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
            }
        } // End __construct()

        public function init_form_fields()
        {
            $this->form_fields = array(
                'enabled' => array(
                    'title'		=> __( 'Enable / Disable' ),
                    'label'		=> __( 'Enable this payment gateway'),
                    'type'		=> 'checkbox',
                    'default'	=> 'no',
                ),
                'title' => array(
                    'title'		=> __( 'Title'),
                    'type'		=> 'text',
                    'desc_tip'	=> __( 'Payment title the customer will see during the checkout process.' ),
                    'default'	=> __( 'LiqPay'),
                ),
                'description' => array(
                    'title'		=> __( 'Description'),
                    'type'		=> 'textarea',
                    'desc_tip'	=> __( 'Payment description the customer will see during the checkout process.'),
                    'default'	=> __( 'Pay securely using your credit card.' ),
                    'css'		=> 'max-width:350px;'
                ),
                'private_key' => array(
                    'title'		=> __( 'private_key'),
                    'type'		=> 'text',
                    'desc_tip'	=> __( 'LiqPay private_key'),
                ),
                'public_key' => array(
                    'title'		=> __( 'public_key' ),
                    'type'		=> 'text',
                    'desc_tip'	=> __( 'LiqPay public_key'),
                ),
                'mode' => array(
                    'title'		=> __( 'LiqPay Test Mode'),
                    'label'		=> __( 'Enable Test Mode'),
                    'type'		=> 'checkbox',
                    'description' => __( 'Place the payment gateway in test mode.'),
                    'default'	=> 'no',
                )
            );
        }

        public function process_payment( $order_id )
        {
            global $woocommerce;

            // Get this Order's information so that we know
            // who to charge and how much
            $customer_order = new WC_Order($order_id);

            require_once dirname(__FILE__) . '/LiqPay.php';

            $liqpay = new LiqPay($this->public_key, $this->private_key);

            /*error_log(print_r($order_id, true));
            error_log(print_r($this->mode, true));
            error_log(print_r($this->public_key, true));
            error_log(print_r($this->private_key, true));
            error_log(print_r($liqpay, true));
            error_log(print_r($_POST, true));*/

            $payload = array();
            $card = '';
            $card_cvv = '';
            $card_expiry = '';
            $card_exp_month = '';
            $card_exp_year = '';
            $card = str_replace( array(' ', '-' ), '', $_POST['liqpay_payment_gateway-card-number'] );
            $card_cvv = isset( $_POST['liqpay_payment_gateway-card-cvc'] ) ? $_POST['liqpay_payment_gateway-card-cvc'] : '';
            $card_expiry = explode('/', $_POST['liqpay_payment_gateway-card-expiry']);

            //error_log(print_r($card_expiry, true));

            $card_exp_month = $card_expiry[0];
            $card_exp_year = $card_expiry[1];

            $payload = array(
                'action'         => 'pay',
                'version'        => '3',
                'phone'          => $customer_order->billing_phone,
                'amount'         => $customer_order->order_total,
                'currency'       => 'USD',
                'description'    => 'description text',
                'order_id'       => $customer_order->get_order_number(),
                'card'           => $card,
                'card_exp_month' => $card_exp_month,
                'card_exp_year'  => $card_exp_year,
                'card_cvv'       => $card_cvv
            );

            if($this->mode == 'yes')
                $payload['sandbox'] = '1';//Для включения тестового режима

            error_log(print_r($payload, true));

            $response = $liqpay->api("request", $payload);

            error_log(print_r($response, true));

        }

        // Validate fields
        public function validate_fields() {
            return true;
        }

        // Check if we are forcing SSL on checkout pages
// Custom function not required by the Gateway
        public function do_ssl_check() {
            if( $this->enabled == "yes" ) {
                if( get_option( 'woocommerce_force_ssl_checkout' ) == "no" ) {
                    echo "<div class=\"error\"><p>". sprintf( __( "<strong>%s</strong> is enabled and WooCommerce is not forcing the SSL certificate on your checkout page. Please ensure that you have a valid SSL certificate and that you are <a href=\"%s\">forcing the checkout pages to be secured.</a>" ), $this->method_title, admin_url( 'admin.php?page=wc-settings&tab=checkout' ) ) ."</p></div>";
                }
            }
        }

    }
}