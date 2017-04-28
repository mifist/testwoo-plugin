<?php

/**
 * Created by PhpStorm.
 * User: romansolomashenko
 * Date: 25.04.17
 * Time: 3:17 PM
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
if ( ! class_exists( 'WC_Novaya_Pochta_Shipping_Method' ) ) {
    class WC_Novaya_Pochta_Shipping_Method extends WC_Shipping_Method
    {
        /**
         * Constructor for your shipping class
         *
         * @access public
         * @return void
         */
        public function __construct() {
            $this->id                 = 'novaya_pochta_shipping_method';
            $this->title       = __( 'Novaya Pochta' );
            $this->method_title = __( 'Novaya Pochta' ); //
            $this->method_description = __( 'Description of Novaya Pochta shipping method' ); //
            //$this->enabled            = "yes"; // This can be added as an setting but for this example its forced enabled

            

            // Availability & Countries
            $this->availability = 'including';
            $this->countries = array(
                'US', // Unites States of America
                'CA', // Canada

                'DE', // Germany
                'GB', // United Kingdom
                'IT',   // Italy
                'ES', // Spain
                'HR'  // Croatia
            );


            $this->init();

            $this->enabled = isset( $this->settings['enabled'] ) ? $this->settings['enabled'] : 'yes';
            $this->title = isset( $this->settings['title'] ) ? $this->settings['title'] : __('Novaya Pochta');

           // error_log(print_r($this->settings, true));

        }

        /**
         * Init your settings
         *
         * @access public
         * @return void
         */
        function init() {
            // Load the settings API
            $this->init_form_fields(); // This is part of the settings API. Override the method to add your own settings
            $this->init_settings(); // This is part of the settings API. Loads settings you previously init.

            // Save settings in admin if you have any defined
            add_action( 'woocommerce_update_options_shipping_' . $this->id, array( $this, 'process_admin_options' ) );
        }


        /**
         * Define settings field for this shipping
         * @return void
         */
        function init_form_fields() {

            $this->form_fields = array(

                'enabled' => array(
                    'title' => __( 'Enable' ),
                    'type' => 'checkbox',
                    'description' => __( 'Enable this shipping.'),
                    'default' => 'yes'
                ),

                'title' => array(
                    'title' => __( 'Title' ),
                    'type' => 'text',
                    'description' => __( 'Title to be display on site'),
                    'default' => __( 'Novaya Pochta' )
                ),

                'weight' => array(
                    'title' => __( 'Weight (kg)' ),
                    'type' => 'number',
                    'description' => __( 'Maximum allowed weight' ),
                    'default' => 100
                ),

            );

        }

        /**
         * calculate_shipping function.
         *
         * @access public
         * @param mixed $package
         * @return void
         */
        public function calculate_shipping( $package = array() ) {
            // This is where you'll add your rates
            $weight = 0;
            $cost = 0;
            $country = $package["destination"]["country"];

            foreach ( $package['contents'] as $item_id => $values )
            {
                $_product = $values['data'];
                $weight = $weight + $_product->get_weight() * $values['quantity'];
            }

            $weight = wc_get_weight( $weight, 'kg' );

            if( $weight <= 10 ) {

                $cost = 0;

            } elseif( $weight <= 30 ) {

                $cost = 5;

            } elseif( $weight <= 50 ) {

                $cost = 10;

            } else {

                $cost = 20;

            }

            $countryZones = array(
                'HR' => 0,
                'US' => 3,
                'GB' => 2,
                'CA' => 3,
                'ES' => 2,
                'DE' => 1,
                'IT' => 1
            );

            $zonePrices = array(
                0 => 10,
                1 => 30,
                2 => 50,
                3 => 70
            );

            $zoneFromCountry = $countryZones[ $country ];
            $priceFromZone = $zonePrices[ $zoneFromCountry ];

            $cost += $priceFromZone;

            $rate = array(
                'id' => $this->id,
                'label' => $this->title,
                'cost' => $cost
            );

            $this->add_rate( $rate );

        }
    }
}