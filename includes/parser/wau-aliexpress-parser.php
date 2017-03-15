<?php
/**
 * Created by PhpStorm.
 * User: JXS
 * Date: 2017/3/6
 * Time: 10:23
 */

include_once('simple_html_dom.php');

class WAU_Aliexpress_Parser {

    function __construct() {
        ini_set('user_agent', 'Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36');
        //ini_set ('memory_limit', 65015808);
    }

    /**
     {
    "skuAttr": "14:193#XWFS01 White;5:100014064#Asia S",
    "skuPropIds": "193,100014064",
    "skuVal": {
        "actSkuCalPrice": "2.99",
        "actSkuMultiCurrencyCalPrice": "2.99",
        "actSkuMultiCurrencyDisplayPrice": "2.99",
        "availQuantity": 996,
        "inventory": 999,
        "isActivity": true,
        "skuCalPrice": "3.15",
        "skuMultiCurrencyCalPrice": "3.15",
        "skuMultiCurrencyDisplayPrice": "3.15"
    }
     * @param $ALIID
     * @return array
     */
    public function get_product_info($ALIID) {
        $infos = array();
        $skus = array();

        if(empty($ALIID)) {
            return $infos;
        }

        try {
            // create HTML DOM
            $url = $this->formUrl($ALIID);
            $contents = $this->download($url);
            if (!empty($contents))
            {
                $dom = $this->get_dom($contents);

                $json = $this->get_json_string($contents);
                //var_dump($json);
                if (!empty($json))
                {
                    $skus_json = json_decode($json);
                    if(!empty($skus_json))
                    {
                        foreach ($skus_json as $sku)
                        {
                            $sku_info = array();

                            $skuPropIds = $sku->skuPropIds; //"skuPropIds": "193,361386",
                            $sku_info['skuPropNames'] = $this->get_varation_names($dom, $skuPropIds);
                            $sku_info['availQuantity'] = $sku->skuVal->availQuantity;
                            $sku_info['inventory'] = $sku->skuVal->inventory;
                            $sku_info['regular_price'] = $sku->skuVal->skuCalPrice; //regular price
                            if(isset($sku->skuVal->actSkuCalPrice)) {
                                $sku_info['sale_price'] = $sku->skuVal->actSkuCalPrice; //regular price
                            } else {
                                $sku_info['sale_price'] = $sku_info['regular_price'];
                            }

                            $skus[] = $sku_info;
                        }

                        //reivews;
                        sleep(1);
                        $infos['skus'] = $skus;
                        $infos['reviews'] = $this->get_reviews($dom);
                    }
                }

                // clean up memory
                $dom->clear();
                unset($dom);
            }
        } catch(Exception $e) {
            //TODO. add expection handler
            if(!empty($dom)) {
                $dom->clear();
                unset($dom);
            }
            $infos = array();
        }

        //var_dump($infos);
        return $infos;
    }

    /*
     * <a data-role="sku" data-sku-id="361386" id="sku-2-361386" href="javascript:void(0)" ><span>M</span></a>
     *  We will get "M"
     *
     * return an array of all variations names
     */
    private function get_varation_names($dom, $skuPropIds)
    {
        $names = array();
        if (!empty($skuPropIds))
        {
            $ids = explode(',', $skuPropIds);
            //var_dump($ids);
            foreach ($ids as $id)
            {
                $xpath = sprintf('a[data-sku-id=%s]', $id);
                $node = $dom->find($xpath, 0);
                if(!empty($node))
                {
                    //the name is either in title, or in <span>
                    $kid = $node->first_child();
                    if($kid->tag == 'span')
                    {
                        $names[] = $kid->innertext;
                    }
                    else
                    {
                        $names[] = $node->title;
                    }
                }
            }
        }

        //var_dump($names);
        return $names;
    }

    private function get_json_string($html) {
        $json = '';

        $start_pos_prefix = 'var skuProducts=';
        $start_flag = $start_pos_prefix . '[{"skuAttr":"';
        $end_flag = '}];';
        $prefix_len = strlen($start_pos_prefix);

        $start_pos = stripos($html, $start_flag);
        if($start_pos > 0) {
            $end_pos = stripos($html, $end_flag, $start_pos);
            if($end_pos > $start_pos) {
                $json = substr($html, $start_pos + $prefix_len, $end_pos - $start_pos - $prefix_len + 2);
            }
        }

        return $json;
    }

    public function get_product_info2($ALIID) {
        $ret = array();

        if(empty($ALIID)) {
            return $ret;
        }
        // create HTML DOM
        $url = $this->formUrl($ALIID);
        $html = $this->get_dom($url);

        if($html != FALSE) {
            //$totalPrice = $html->find('a[class="store-lnk"]', 0)->innertext();
            //var_dump($totalPrice);

            //sku properties are stored in a JSON string, first retrieve it.
        }
        // clean up memory
        $html->clear();
        unset($html);

        return $ret;
    }



    /**
     * the ALIID numbers are taken and open a url – 32249071594
    - this opens each product number page; eg; https://www.aliexpress.com/item/item/32249071594.html
     * @param $ALIID
     */
    public function formUrl($ALIID) {
        $url = sprintf('https://www.aliexpress.com/item/item/%s.html', $ALIID);
        return $url;
    }

    public function get_dom($contents, $use_include_path = false, $context=null, $offset = -1,
                            $maxLen=-1, $lowercase = true, $forceTagsClosed=true,
                            $target_charset = DEFAULT_TARGET_CHARSET,
                            $stripRN=true,
                            $defaultBRText=DEFAULT_BR_TEXT,
                            $defaultSpanText=DEFAULT_SPAN_TEXT) {
        // We DO force the tags to be terminated.
        $dom = new simple_html_dom(null, $lowercase, $forceTagsClosed, $target_charset, $stripRN, $defaultBRText, $defaultSpanText);

        // Paperg - use our own mechanism for getting the contents as we want to control the timeout.
        if (empty($contents) || strlen($contents) > MAX_FILE_SIZE)
        {
            return false;
        }
        // The second parameter can force the selectors to all be lowercase.
        $dom->load($contents, $lowercase, $stripRN);
        return $dom;
    }

    public function download($url) {

        $ret = '';
        $file = fopen ($url, "r");
        if ($file) {
            while (!feof($file)) {
                $line = fgets($file, 1024);
                //print($line);
                $ret .= $line;
            }
            fclose($file);
        }

        return $ret;
    }

    /*
    public function download($url) {
        $opts = array(
            'http'=>array(
                'method'=>"GET",
                'header'=>"user-agente: Mozilla/5.0 (Windows NT 6.1) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/56.0.2924.87 Safari/537.36\r\n"

            )
        );

        $context = stream_context_create($opts);

        // Open the file using the HTTP headers set above
        $file = file_get_contents($url, false, $context);

        return $file;
    }
    */



    public function get_remote_data($url, $post_paramtrs = false) {
        $c = curl_init();
        curl_setopt($c, CURLOPT_URL, $url);
        curl_setopt($c, CURLOPT_RETURNTRANSFER, 1);
        if ($post_paramtrs) {
            curl_setopt($c, CURLOPT_POST, TRUE);
            curl_setopt($c, CURLOPT_POSTFIELDS, "var1=bla&" . $post_paramtrs);
        } curl_setopt($c, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($c, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($c, CURLOPT_USERAGENT, "Mozilla/5.0 (Windows NT 6.1; rv:33.0) Gecko/20100101 Firefox/33.0");
        curl_setopt($c, CURLOPT_COOKIE, 'CookieName1=Value;');
        curl_setopt($c, CURLOPT_MAXREDIRS, 10);
        $follow_allowed = ( ini_get('open_basedir') || ini_get('safe_mode')) ? false : true;
        if ($follow_allowed) {
            curl_setopt($c, CURLOPT_FOLLOWLOCATION, 1);
        }curl_setopt($c, CURLOPT_CONNECTTIMEOUT, 9);
        curl_setopt($c, CURLOPT_REFERER, $url);
        curl_setopt($c, CURLOPT_TIMEOUT, 60);
        curl_setopt($c, CURLOPT_AUTOREFERER, true);
        curl_setopt($c, CURLOPT_ENCODING, 'gzip,deflate');
        $data = curl_exec($c);
        $status = curl_getinfo($c);
        curl_close($c);
        preg_match('/(http(|s)):\/\/(.*?)\/(.*\/|)/si', $status['url'], $link);
        $data = preg_replace('/(src|href|action)=(\'|\")((?!(http|https|javascript:|\/\/|\/)).*?)(\'|\")/si', '$1=$2' . $link[0] . '$3$4$5', $data);
        $data = preg_replace('/(src|href|action)=(\'|\")((?!(http|https|javascript:|\/\/)).*?)(\'|\")/si', '$1=$2' . $link[1] . '://' . $link[3] . '$3$4$5', $data);
        if ($status['http_code'] == 200) {
            return $data;
        } elseif ($status['http_code'] == 301 || $status['http_code'] == 302) {
            if (!$follow_allowed) {
                if (empty($redirURL)) {
                    if (!empty($status['redirect_url'])) {
                        $redirURL = $status['redirect_url'];
                    }
                } if (empty($redirURL)) {
                    preg_match('/(Location:|URI:)(.*?)(\r|\n)/si', $data, $m);
                    if (!empty($m[2])) {
                        $redirURL = $m[2];
                    }
                } if (empty($redirURL)) {
                    preg_match('/href\=\"(.*?)\"(.*?)here\<\/a\>/si', $data, $m);
                    if (!empty($m[1])) {
                        $redirURL = $m[1];
                    }
                } if (!empty($redirURL)) {
                    $t = debug_backtrace();
                    return call_user_func($t[0]["function"], trim($redirURL), $post_paramtrs);
                }
            }
        } return "ERRORCODE22 with $url!!<br/>Last status codes<b/>:" . json_encode($status) . "<br/><br/>Last data got<br/>:$data";
    }


    /////////////////////////////////////// Reviews /////////////////////////
    /*
     *
     * Each review is an array of [conents, time]
     */
    public function get_reviews($dom) {

        $reviews = array();
        try {
            //it's in a iframe
            // <div class="ui-tab-pane" data-role="panel" id="feedback">
            //   <iframe scrolling="no" frameborder="0" marginwidth="0" marginheight="0" width="100%" height="200" thesrc="//feedback.aliexpress.com/display/productEvaluation.htm?productId=32270204549&ownerMemberId=206295541&companyId=218666814&memberType=seller&startValidDate=&i18n=true"></iframe>
            $url_node = $dom->find('div[id="feedback"] iframe', 0);
            if (!empty($url_node)) {
                $url = $url_node->thesrc;
                if (stripos($url, 'http') !== 0) {
                    $url = 'https:' . $url;
                }
                if (!empty($url)) {
                    $contents = $this->download($url);
                    if (!empty($contents)) {
                        $review_dom = $this->get_dom($contents);
                        $reviews = $this->get_reviews_helper($review_dom);

                        $review_dom->clear();
                        unset($review_dom);
                    }
                }
            }
        }catch(Exception $e) {
            //we do nothing for now
        }

        return $reviews;
    }

    private function get_reviews_helper($dom)
    {
        //parent node <dl class="buyer-review">
        //<dt class="buyer-feedback"></dl>
        $nodes = $dom->find('dl[class="buyer-review"]');
        if(empty($nodes))
        {
            var_dump('nodes are empty');
        }
        $reviews = array();
        if(!empty($nodes))
        {
            foreach ($nodes as $node)
            {
                $review = array();

                $contents_node = $node->find('dt[class=buyer-feedback]', 0);
                if(!empty($contents_node))
                {
                    $contents = $contents_node->plaintext;
                    if(!empty($contents)) {
                        $contents = trim($contents);
                    }
                    $review['contents'] = $contents;
                }

                $time_node = $node->find('dd[class=r-time]', 0);
                if(!empty($time_node))
                {
                    $review['time'] = $time_node->plaintext;
                }

                //var_dump($review); var_dump('<br>');

                $reviews[] = $review;
                /***
                $kids = $nodes->children;
                var_dump($node->innerhtml); var_dump('<br>');
                if(!empty($kids))
                {
                    $review = array();
                    foreach ($kids as $kid)
                    {
                        if($kid->class = 'buyer-feedback')
                        {
                            $review['contents'] = $kid->plaintext;
                        }
                        else if ($kid->class = 'r-time')
                        {
                            $review['time'] = $kid->plaintext;
                        }

                        var_dump($review); var_dump('<br>');
                    }
                    $reviews[] = $review;
                 */
                }
            }

        return $reviews;
    }
}

//$ali_parser = new Aliexpress_Parser();
//$ali_parser->get_product_info('32249071594');