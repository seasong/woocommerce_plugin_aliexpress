<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}


/**
 * WooCommerce AliExpress Updater Backend
 *
 * Allows admin to set WooCommerce AliExpress Updater
 *
 * @class   Woo_aliexpress_updater_backend
 */


class WAU_Backend {

    private $option_name_price_range_markup = '_wau_price_range_markup';
    private $option_name_regprice_range_markup = '_wau_regprice_range_markup'; //reguar price
    private $option_name_product_updater = '_wau_product_updater';

    /**
     * Instance of this class.
     *
     * @var object
     */
    protected static $instance = null;

    /**
     * Init and hook in the integration.
     *
     * @return void
     */
    public function __construct() {
        $this->id = 'Woo_aliexpress_updater_backendd';
        $this->method_title = __('WooCommerce AliExpress Updater Backend', 'woo-aliexpress-updater');
        $this->method_description = __('Ali Updater ', 'woo-aliexpress-updater');

        //add menu under "WooCommerce"
        add_action('admin_menu', array($this, 'register_my_custom_submenu_page'));
        add_action('admin_post_submit-form', array($this, 'handle_form_action'));
        add_action('admin_post_submit-form-reg-price', array($this, 'handle_form_action_reg_price'));

        //add an option, with empty value
        add_option($this->option_name_price_range_markup, '');
        add_option($this->option_name_regprice_range_markup, '');
    }



    public function handle_form_action(){

        //1. save to wp_options table
        $new_value = '';
        for($index = 1; $index < 5; $index++) {
            $new_value .= $_REQUEST['wau_price_range_from_'.$index] . ',';
            $new_value .= $_REQUEST['wau_price_range_to_'.$index] . ',';
            $add_type = $_REQUEST['wau_price_range_type_'.$index];
            $add_type == 'num' ? $add_type = '$' : $add_type = '%';
            $new_value .= $add_type . ',';
            $new_value .= $_REQUEST['wau_price_range_value_'.$index] . ';';
        }

        $new_value = trim($new_value, ';');

        update_option($this->option_name_price_range_markup, $new_value);

        //2. Loop the product to update the price
        //$markup = [0, 10, '$', 5];
        $this->UpdatePrice();

        wp_redirect($_REQUEST['_wp_http_referer']);
    }

    public function handle_form_action_updater(){

        //1. save to wp_options table
        $new_value = $_REQUEST['updater_hour'] . ',' . $_REQUEST['updater_minute'];
        update_option($this->option_name_product_updater, $new_value);

        wp_redirect($_REQUEST['_wp_http_referer']);
    }

    public function auto_update() {
        // auto update....
        include_once 'wau-aliexpress-updater.php';
        $ali_updater = new WAU_Aliexpress_Updater();
        $meta_rows = $ali_updater->get_aliids();
        $ali_updater->refresh($meta_rows);
        // auto update done...
    }

    public function get_start_time() {
        $ret = [0,0];
        $value = get_option($this->option_name_product_updater);
        if(!empty($value)) {
            $ret = explode(',', $value);
        }

        return $ret;
    }

    public function UpdatePrice() {
        $markups = $this->get_price_range_markups();
        for($index = 1; $index < 5; $index++) {
            $markup = $this->get_price_range_markup($markups, $index - 1);
            $this->UpdatePriceHelper($markup);
        }
    }

    /*
     * say we take USD price of each product
     * Lets say a product = $10 (simple product)
     * and we have markup of $5
     * Means the sale price = $15
     *
     * for one markup
     */
    public function UpdatePriceHelper($markup)
    {
        global $wpdb;
        $meta_key_sale = '_sale_price';

        if (!empty($markup) && count($markup) == 4) {
            $from = $markup[0];
            $to = $markup[1];
            $type = $markup[2];
            $value = $markup[3];

            // $0 and $10 = + $5
            if ($to >= $from && $value != 0) {
                $sql = 'SELECT post_id, meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key="' . $meta_key_sale . '"';
                $ret = $wpdb->get_results($sql);
                if (is_array($ret)) {
                    foreach ($ret as $row) {
                        $id = $row->post_id;
                        $price = $row->meta_value;
                        if ($price >= $from AND $price < $to) {
                            if ($type == '$') {
                                update_post_meta($id, $meta_key_sale, $price + $value);
                            } else {
                                update_post_meta($id, $meta_key_sale, round($price * (100 + $value) / 100,2));
                            }
                        }
                    }
                }
            }
        }
    }


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

    public function register_my_custom_submenu_page() {
        add_submenu_page('woocommerce', 'Ali Updater', 'Ali Updater', 'manage_options',
            'woo_ali_updater', array($this, 'woo_ali_updater_page'));
    }

    public function woo_ali_updater_page() {
        include_once 'html-wau-settings.php';
    }


    // wp_options
    public function get_price_range_markups() {
        $ret = array();
        $value= get_option($this->option_name_price_range_markup);

        if(!empty($value)) {
            $ret = explode(';', $value);
        }

        return $ret;
    }

    public function get_price_range_markup($markups, $index) {

        $ret = [0, 0, '$', 0]; //has a default value anyway
        //0,10,$,5
        if(count($markups) > $index) {
            $cols = explode(',', $markups[$index]);
            if(count($cols) === 4) {
                $ret = [$cols[0], $cols[1], $cols[2], $cols[3]];
            }
        }
        return $ret;
    }

    ///////////////////////////////////For regular price//////////////////////////////
    // wp_options
    public function get_regprice_range_markups() {
        $ret = array();
        $value= get_option($this->option_name_regprice_range_markup);

        if(!empty($value)) {
            $ret = explode(';', $value);
        }

        return $ret;
    }

    /*
     * Form submit, save to db only. Will update later, when retrieving aliexpress.com
     */
    public function handle_form_action_reg_price(){
        //1. save to wp_options table
        $new_value = '';
        for($index = 1; $index < 5; $index++) {
            $new_value .= $_REQUEST['wau_regprice_range_from_'.$index] . ',';
            $new_value .= $_REQUEST['wau_regprice_range_to_'.$index] . ',';
            $add_type = $_REQUEST['wau_regprice_range_type_'.$index];
            $add_type == 'num' ? $add_type = '$' : $add_type = '%';
            $new_value .= $add_type . ',';
            $new_value .= $_REQUEST['wau_regprice_range_value_'.$index] . ';';
        }

        $new_value = trim($new_value, ';');

        update_option($this->option_name_regprice_range_markup, $new_value);

        wp_redirect($_REQUEST['_wp_http_referer']);
    }


    public function update_reg_price($post_id, $reg_price) {

        $markups = $this->get_regprice_range_markups();
        for($index = 1; $index < 5; $index++) {
            $markup = $this->get_price_range_markup($markups, $index - 1);
            $this->UpdateRegPriceHelper($markup,$post_id, $reg_price);
        }
    }
    /*
     *
     * $sale_price
     *  taking NEW sale price your plugin generates, and for example + $10,
     *    this is then the regular price for this product
     */
    public function UpdateRegPriceHelper($markup,$post_id, $sale_price)
    {
        global $wpdb;
        $meta_key_regular = '_regular_price';;

        if (!empty($markup) && count($markup) == 4) {
            $from = $markup[0];
            $to = $markup[1];
            $type = $markup[2];
            $value = $markup[3];

            // $0 and $10 = + $5
            if ($to >= $from && $value != 0) {
                if ($sale_price >= $from AND $sale_price < $to) {
                    if ($type == '$') {
                        update_post_meta($post_id, $meta_key_regular, $sale_price + $value);
                    } else {
                        update_post_meta($post_id, $meta_key_regular, round($sale_price * (100 + $value) / 100, 2));
                    }
                }
            }
        }
    }

}

$wau_backend = new WAU_Backend();