<?php
/**
 * WooCommerce AliExpress Price Range Markup Settings
 *
 * @author      WooThemes
 * @category    Admin
 * @package     WooCommerce/Admin
 * @version     1.0.01
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}

if ( ! class_exists( 'WAU_Settings_Price_Range' ) ) :

    /**
     * WAU_Settings_Price_Range.
     */
    class WAU_Settings_Price_Range extends WC_Settings_Page {

        /**
         * Constructor.
         */
        public function __construct() {

            $this->id    = 'wau_price_range';
            $this->label = __( 'Price Range Markup', 'woo_aliexpress_price_range_markup' );

            add_action( 'wau_price_range_settings' , array( $this, 'output' ) );
        }

        /**
         * Get settings array.
         *
         * @return array
         */
        public function get_settings() {


        $settings = apply_filters( 'woocommerce_general_settings', array(


                array(
                    'title'    => __( 'Number of Decimals', 'woocommerce' ),
                    'desc'     => __( 'This sets the number of decimal points shown in displayed prices.', 'woocommerce' ),
                    'id'       => 'wau_price_range_1_from',
                    'css'      => 'width:50px;',
                    'default'  => '2',
                    'desc_tip' =>  true,
                    'type'     => 'number',
                    'custom_attributes' => array(
                        'min'  => 0,
                        'step' => 1
                    )
                ),



            ) );

            return apply_filters( 'woocommerce_get_settings_' . $this->id, $settings );
        }


        /**
         * Save settings.
         */
        public function save() {
            $settings = $this->get_settings();

            WC_Admin_Settings::save_fields( $settings );
        }

    }

endif;

return new WAU_Settings_Price_Range();
