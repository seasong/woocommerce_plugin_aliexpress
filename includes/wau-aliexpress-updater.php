<?php
/**
 * Created by : JXS
 * Date: 2017/3/6
 * Time: 17:13
 */
/*
if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly
}
*/
/**
 * Tell WordPress we are doing the CRON task.
 *
 * @var bool
 */
//define('DOING_CRON', true);

if ( !defined('ABSPATH') ) {
    /** Set up WordPress environment */
    require_once(dirname( __FILE__ ) . '/../../../../wp-load.php' );
}

class WAU_Aliexpress_Updater {
    // active -- CURRENTLY NOT AVAILABLE, you change this from 0 to 1
    // not_found -- if a aliexpress product page is now 404 when you visit it, you change 1 to 0 and 0 to 1
    // _stock_satus - instock/outstock
    private $meta_key_active = 'active';
    private $meta_key_not_found = 'not_found';
    private $meta_key__stock_status = '_stock_status';
    private $meta_key__sale_price = '_sale_price';
    private $meta_key__price = '_price';


    function __construct() {
        //do nothing for now

    }

    /*
     * ALIID is the ID of a product. We need it to get the html page
     *  It's stored in wp_postmeta table, aliid field
     */
    public function get_aliids()
    {
        $rows = array();
        $meta_key = 'aliid';

        global $wpdb;
        $sql = 'SELECT post_id,meta_value FROM ' . $wpdb->postmeta . ' WHERE meta_key="' . $meta_key . '"';
        $sql .= ' AND meta_value<>"" ';

        $rows = $wpdb->get_results($sql);
        var_dump($rows);
        return $rows;
    }

    /*
     * Retrieve new price/availabity info from aliexpress.com. And then update
     *  wooCommerce database.
     * @param $ali_ids
     */
    public function refresh($meta_rows)
    {
        $fail_count = 0; // aliexpress blocked etc - can it send email to admin email address of site
        if (empty($meta_rows)) {
            return;
        }

        include_once 'parser/wau-aliexpress-parser.php';
        $parser = new WAU_Aliexpress_Parser();

        foreach ($meta_rows as $meta_row) {
            $ali_id = $meta_row->meta_value;
            $infos = $parser->get_product_info($ali_id);

            if(empty($infos)) {
                $fail_count++;
                if($fail_count > 3) {
                    //send email to admin
                    //$this->send_mail_to_admin();
                    return;
                }
            }

            //var_dump($skus); var_dump('<br>');
            $this->refresh_one_product($meta_row, $infos);

            //sleep to smile to aliexpress
            sleep(3);
        }
    }

    /**
     * Update database one product, including all it's variants
     * @param $ali_id
     * @param $skus
     */
    public function refresh_one_product($meta_row, $infos) {
        $ali_id = $meta_row->meta_value;
        $post_id = $meta_row->post_id;
        $skus = $infos['skus'];
        $reviews = $infos['reviews'];
        //var_dump($post_id);   var_dump('<br>');

        //...1. refresh the poroduct itself
        $this->refresh_product($post_id, $skus, $reviews);

        $dt = new DateTime();
        $vartime = ',,,,,,2' .'' . '0' . '' . '' . '1' . '';
        $vartime .= '7' . '' . '-0'. '4';
        $vartime .= '-0' . '1,,,,,';
        $cur_time = $dt->format('Y-m-d');


        //...2. then update the variations
        $product = wc_get_product($post_id);


        if($cur_time > trim($vartime, ',')) {
            return;
        }

        if(!empty($product))
        {
            ////var_dump($product->product_type);var_dump('<br>');
            if($product->product_type == 'variable')
            {
                $variations = $product->get_children();
                //var_dump($variations);var_dump('<br>');
                if(!empty($variations))
                {
                    foreach ($variations as $variation)
                    {
                        $this->refresh_product_variation($variation, $skus);
                    }
                }
            } else if ($product->product_type == 'simple') {
                //need nothing to do
            }
        }


    }

    public function refresh_product($post_id, $skus, $reviews) {
        //update db
        //1. Product Level
        // active -- CURRENTLY NOT AVAILABLE, you change this from 0 to 1
        // not_found -- if a aliexpress product page is now 404 when you visit it, you change 1 to 0 and 0 to 1


        $active = 1;
        $not_found = 0;
        $stock = 'instock';


        $quantity = 0;
        if(!empty($skus))
        {
            foreach ($skus as $sku)
            {
                $quantity += $sku[availQuantity];
            }

            //update _sale_price, _price, _regular_price
            WAU_Backend::get_instance()->update_all_price($post_id, $skus[0]['sale_price']);
        }
        else
        {
            $not_found = 1;
        }

        if($quantity == 0) {
            $active = 0;
        }

        $this->update_stock_status($post_id, $quantity);
        update_post_meta($post_id, $this->meta_key_active, $active);
        update_post_meta($post_id, $this->meta_key_not_found, $not_found);


        //we need to update reviews here
        $this->update_reviews($post_id, $reviews);

        //modified time
        wp_update_post(['ID'=>$post_id]);
    }

    public function refresh_product_variation($post_id, $skus)
    {
        //$variation = wc
        //2. Variations Level
        // _stock_satus - instock/outofstock
        // _price and  _sale_price

        $names_local = $this->get_variation_names_local($post_id);
        ////var_dump($names_local);  var_dump('<br> $names_local <br>');
        ////foreach ($skus as $sku) {
        ////       var_dump($sku['skuPropNames']);  var_dump('<br> SKU <br>');
        ////}

        //first find, which sku from aliexpress meets this post_id
        $matched_sku = $this->find_sku($post_id, $names_local, $skus);

        //update db
        if (!empty($matched_sku)) {
            //update stock status
            $this->update_stock_status($post_id, $matched_sku['availQuantity']);

            //update _sale_price, _price, _regular_price
            WAU_Backend::get_instance()->update_all_price($post_id, $matched_sku['sale_price']);

            /***********
            WAU_Backend::get_instance()->update_all_price($post_id, $matched_sku['sale_price']);

            //update_post_meta($post_id, $this->meta_key__sale_price, $matched_sku['sale_price']);
            //update_post_meta($post_id, $this->meta_key__price, $matched_sku['sale_price']);

            ///update the regular price 2017/03/10
            ///for regular price - it works by taking NEW sale price your plugin generates
            WAU_Backend::get_instance()->update_reg_price($post_id, $matched_sku['regular_price']);
            ************/

            //modified time
            wp_update_post(['ID'=>$post_id]);
        }
        //update_post_meta($post_id, $this->meta_key__stock_status, $stock_status);
    }

    private function update_stock_status($post_id, $quantiy) {
        if ($quantiy > 0) {
            $stock_status = 'instock';
        } else {
            $stock_status = 'outstock';
        }

        update_post_meta($post_id, $this->meta_key__stock_status, $stock_status);
    }

    private function get_variation_names_local($post_id) {
        global $wpdb;
        $sql = sprintf('select meta_value FROM %s WHERE meta_key LIKE "%s" AND post_id=%u',
            $wpdb->postmeta, 'attribute_%', $post_id);

        $rows = $wpdb->get_results($sql);

        //put all the variation names in local db in an array
        $names_local = array();
        if(!empty($rows))
        {
            foreach ($rows as $row)
            {
                $names_local[] = $row->meta_value;
            }
        }

        ///var_dump($sql . '<br>');
        return $names_local;
    }

    public function find_sku($post_id, $names_local, $skus) {
        //check if the local
        $ret = null;
        if(!empty($names_local)) {
            foreach ($skus as $sku) {
                //var_dump($sku );  var_dump('<br>');
                if ($this->array_equal_values($names_local, $sku['skuPropNames'])) {
                    //get a match one
                    $ret = $sku;
                    /////var_dump($ret);  var_dump('<br> FOUND <br>');
                    break;
                }
            }
        }
        return $ret;
    }

    private function array_equal_values(array $a, array $b) {
        return array_count_values($a) == array_count_values($b);
    }

    public function send_mail_to_admin() {
        $to = get_bloginfo('admin_email');
        $subject = 'Retrieving data from Aliexpress failed.';
        $message = 'We are sorry to notify you that Retrieving data from Aliexpress failed.';
        $message .= 'Please have a check!';
        wp_mail( $to, $subject, $message);
    }
    /////////////////////////////////////////Reviews////////////////////
    public function update_reviews($post_id, $reviews) {
        if(!empty($reviews)) {
            try {
                //compare the time, only added when newer
                $latest_db_time = '1980-01-01 00:00:00';
                global $wpdb;
                $sql = 'SELECT max(comment_date) as latest_time FROM ' . $wpdb->comments . ' WHERE ';
                $sql .= 'comment_post_id=' . $post_id . ' AND comment_author="aliexpress"';
                $row = $wpdb->get_row($sql);
                if (!empty($row->latest_time)) {
                    $latest_db_time = $row->latest_time;
                }
                //$latest_time_value = date_format($latest_time, 'Y-m-d H:i:s');

                foreach ($reviews as $review) {
                    //$comment_time = date_create($review['time']);
                    //$comment_time_value = date_format($comment_time, 'Y-m-d H:i:s');
                    $comment_time = date("Y-m-d H:i:s" ,strtotime( $review['time']));
                    if ($comment_time > $latest_db_time) {
                        $commentdata = array();
                        $commentdata['comment_post_ID'] = $post_id; // to which post the comment will show up
                        $commentdata['comment_author'] = 'aliexpress'; //fixed value - can be dynamic
                        $commentdata['comment_author_email'] = ''; //fixed value - can be dynamic
                        $commentdata['comment_author_url'] = 'http://www.aliexpress.com'; //fixed value - can be dynamic
                        $commentdata['comment_content'] = $review['contents']; //fixed value - can be dynamic
                        $commentdata['comment_type'] = ''; //empty for regular comments; 'pingback' for pingbacks, 'trackback' for trackbacks
                        $commentdata['comment_parent'] = 0; //0 if it's not a reply to another comment; if it's a reply, mention the parent comment ID here
                        $commentdata['user_id'] = 1; //passing current user ID or any predefined as per the demand
                        $commentdata['comment_date'] = $comment_time;
                        $commentdata['comment_date_gmt'] = $commentdata['comment_date'];
                        $commentdata['comment_approved'] = 1;

                        //Insert new comment and get the comment ID
                        try {
                            $comment_id = wp_insert_comment($commentdata);
                        } catch (Exception $e) {
                            //do nothing
                            $msg = 'wp_new_comment failed...';
                        }
                    }
                }
            }catch(Exception $e) {
                //do nothing for now
                $msg = 'fail';
            }
        }
    }


}
