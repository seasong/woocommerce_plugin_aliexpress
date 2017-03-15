<?php
/**
 * Admin View: AliExpress Price Range Settings
 */
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

$markups = WAU_Backend::get_instance()->get_price_range_markups();
$regmarkups = WAU_Backend::get_instance()->get_regprice_range_markups(); //for regular price
$action_url = get_admin_url().'admin-post.php';

/*
$to = 'seasong@139.com';
$subject = 'Retrieving data from Aliexpress failed.';
$message = 'We are sorry to notify you that Retrieving data from Aliexpress failed. \n';
$message .= 'Please have a check!\n';
wp_mail( $to, $subject, $message);
*/

//for testing purpose
//include_once 'parser/wau-aliexpress-parser.php';
//$parser = new WAU_Aliexpress_Parser();
//$parser->get_product_info('32270204549');
//$parser->get_product_info('SKU2392 SKU2393');

/*
include_once 'wau-aliexpress-updater.php';
$ali_updater = new WAU_Aliexpress_Updater();
$meta_rows = $ali_updater->get_aliids();
var_dump($meta_rows); var_dump('<br>');
$ali_updater->refresh($meta_rows);
*/
//$ali_updater->find_sku(12, null);

/*
//for testing
$product = wc_get_product(8); //8 is a variable parent product
$product->set_stock(99);
$product->sale_price = '99.99';
wc_update_product_stock(8, 99);
wc_update_product_stock_status(8, 'outofstock');

//var_dump($product);
$kids = $product->get_children();
if(!empty($kids)) {
    foreach($kids as $kid) {
        var_dump($kid);
        var_dump('<br>');
    }
}
//for testing purpose end
*/
?>
<div class="wrap woocommerce">
    <h1>AliExpress Sale Price Range Markup</h1>
    <form id="pricerange" method="post" action="<?php echo $action_url?>" enctype="multipart/form-data">
        <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
        </nav>
        <table class="form-table">
            <tbody>
            <?php
            for($index = 1; $index < 5; $index++) {
                $markup = WAU_Backend::get_instance()->get_price_range_markup($markups, $index - 1);
                $name_from = 'wau_price_range_from_' . $index;
                $name_to = 'wau_price_range_to_' . $index;
                $name_type = 'wau_price_range_type_' . $index;
                $name_value = 'wau_price_range_value_' . $index;
                $num_selected = '';
                $per_selected = '';
                if($markup[2] == '$')
                    $num_selected = 'selected';
                else
                    $per_selected = 'selected';
            ?>
            <tr valign="top">
                <td class="forminp forminp-number">
                    <label for="<?php echo $name_from?>">Formula&nbsp;&nbsp;<?php echo $index?>&nbsp;From:</label>
                    <input name="<?php echo $name_from?>" id="<?php echo $name_from?>" type="number" style="width:50px;" value="<?php echo $markup[0]?>" class="" placeholder="" min="0" step="10">
                    <label for="<?php echo $name_to?>">To:</label>
                    <input name="<?php echo $name_to?>" id="<?php echo $name_to?>" type="number" style="width:50px;" value="<?php echo $markup[1]?>" class="" placeholder="" min="0" step="10">
                    <label for="<?php echo $name_from?>">&nbsp;&nbsp;=&nbsp;+</label>
                    <select name="<?php echo $name_type?>" id="<?php echo $name_type?>" class="wc-enhanced-select enhanced" tabindex="-1" title="">
                        <option value="num" <?php echo $num_selected?>>$</option>
                        <option value="per" <?php echo $per_selected?>>%</option>
                    </select>
                    <input name="<?php echo $name_value?>" id="<?php echo $name_value?>" type="number" style="width:50px;" value="<?php echo $markup[3]?>" class="" placeholder="" min="0" step="5">
                </td>
            </tr>
            <?php } ?>
            </tbody>
        </table>
        <p class="submit">
            <?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
                <input name="save" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save Sale Price Marks', 'woocommerce' ); ?>" />
            <?php endif; ?>
            <?php wp_nonce_field( 'woocommerce-settings' ); ?>
            <input type='hidden' name='action' value='submit-form' />
            <input type='hidden' name='hide' value='$ques' />
        </p>
    </form>

        <h1>AliExpress Regular Price Range Markup</h1>
        <form id="regpricerange" method="post" action="<?php echo $action_url?>" enctype="multipart/form-data">
            <nav class="nav-tab-wrapper woo-nav-tab-wrapper">
            </nav>
            <table class="form-table">
                <tbody>
                <?php
                for($index = 1; $index < 5; $index++) {
                    $markup = WAU_Backend::get_instance()->get_price_range_markup($regmarkups, $index - 1);
                    $name_from = 'wau_regprice_range_from_' . $index;
                    $name_to = 'wau_regprice_range_to_' . $index;
                    $name_type = 'wau_regprice_range_type_' . $index;
                    $name_value = 'wau_regprice_range_value_' . $index;
                    $num_selected = '';
                    $per_selected = '';
                    if($markup[2] == '$')
                        $num_selected = 'selected';
                    else
                        $per_selected = 'selected';
                    ?>
                    <tr valign="top">
                        <td class="forminp forminp-number">
                            <label for="<?php echo $name_from?>">Formula&nbsp;&nbsp;<?php echo $index?>&nbsp;From:</label>
                            <input name="<?php echo $name_from?>" id="<?php echo $name_from?>" type="number" style="width:50px;" value="<?php echo $markup[0]?>" class="" placeholder="" min="0" step="10">
                            <label for="<?php echo $name_to?>">To:</label>
                            <input name="<?php echo $name_to?>" id="<?php echo $name_to?>" type="number" style="width:50px;" value="<?php echo $markup[1]?>" class="" placeholder="" min="0" step="10">
                            <label for="<?php echo $name_from?>">&nbsp;&nbsp;=&nbsp;+</label>
                            <select name="<?php echo $name_type?>" id="<?php echo $name_type?>" class="wc-enhanced-select enhanced" tabindex="-1" title="">
                                <option value="num" <?php echo $num_selected?>>$</option>
                                <option value="per" <?php echo $per_selected?>>%</option>
                            </select>
                            <input name="<?php echo $name_value?>" id="<?php echo $name_value?>" type="number" style="width:50px;" value="<?php echo $markup[3]?>" class="" placeholder="" min="0" step="5">
                        </td>
                    </tr>
                <?php } ?>
                </tbody>
            </table>
            <p class="submit">
                <?php if ( empty( $GLOBALS['hide_save_button'] ) ) : ?>
                    <input name="save_reg" class="button-primary woocommerce-save-button" type="submit" value="<?php esc_attr_e( 'Save Regular Price Marks', 'woocommerce' ); ?>" />
                <?php endif; ?>
                <?php wp_nonce_field( 'woocommerce-settings' ); ?>
                <input type='hidden' name='action' value='submit-form-reg-price' />
                <input type='hidden' name='hide' value='$ques' />
            </p>
        </form>
</div>
