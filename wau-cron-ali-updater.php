<?php
/**
 * Created by Jason.
 * User: JXS
 * Date: 2017/3/9
 * Time: 11:24
 */

function auto_update() {
    // auto update....
    include_once './includes/wau-aliexpress-updater.php';
    $ali_updater = new WAU_Aliexpress_Updater();

    $meta_rows = $ali_updater->get_aliids();
    //var_dump($meta_rows); var_dump('<br>');
    $ali_updater->refresh($meta_rows);
    // auto update done...
}

//auto_update();
if( isset($_GET['key']) && $_GET['key'] == 'afdlk_uKee_' . 'mniouz_pDIK_') {
    //var_dump('ffff');
    auto_update();
} else {
    //var_dump('ddddddddddddd');
}

?>
