<?php
/*
 * Plugin Name: Ali Updater
 * Plugin URI:
 * Description: Ali Updater for wooCommerce
 * Version: 1.0.1
 * Author: Jason
 * Author URI:
 * Text Domain:
 * License:
 * License URI:
 */

defined( 'ABSPATH' ) or die( 'No script kiddies please!' );  // prevent direct access

if ( ! class_exists( 'Woo_Aliexpress_Updater' ) ) :

    class Woo_Aliexpress_Updater {


        /**
         * Plugin version.
         *
         * @var string
         */
        const VERSION = '1.0.1';

        /**
         * Instance of this class.
         *
         * @var object
         */
        protected static $instance = null;


        /**
         * Initialize the plugin.
         */
        public function __construct(){

            /**
             * Check if WooCommerce is active
             **/
            if ( in_array( 'woocommerce/woocommerce.php', apply_filters( 'active_plugins', get_option( 'active_plugins' ) ) ) ) {

                include_once 'includes/class-wau-backend.php';
            } else {

                add_action( 'admin_init', array( $this, 'wau_plugin_deactivate') );
                add_action( 'admin_notices', array( $this, 'wau_woocommerce_missing_notice' ) );
            }

        } // end of contructor




        /**
         * Return an instance of this class.
         *
         * @return object A single instance of this class.
         */
        public static function get_instance() {
            // If the single instance hasn't been set, set it now.
            if ( null == self::$instance ) {
                self::$instance = new self;
            }

            return self::$instance;
        }

        /**
         * WooCommerce fallback notice.
         *
         * @return string
         */
        public function wau_woocommerce_missing_notice() {
            echo '<div class="error"><p>' . sprintf( __( 'Ali Updater: No active install of %s!', 'woo-aliexpress-updater' ),
                     '<a href="#" target="_blank">' . __( 'WooCommerce', 'woo-aliexpress-updater' ) . '</a>' ) . '</p></div>';
            if ( isset( $_GET['activate'] ) )
                unset( $_GET['activate'] );
        }

        /**
         * WooCommerce fallback notice.
         *
         * @return string
         */
        public function wau_plugin_deactivate() {
            deactivate_plugins( plugin_basename( __FILE__ ) );
        }


    }// end of the class

    add_action( 'plugins_loaded', array( 'Woo_Aliexpress_Updater', 'get_instance' ), 0 );

endif;