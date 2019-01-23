<?php
require_once 'composer/vendor/autoload.php';
#require_once 'util/chatwork.php';
#require_once 'util/nifty_push.php';
use Goutte\Client;
use Symfony\Component\DomCrawler\Crawler;

try {
    date_default_timezone_set('Asia/Tokyo');

    // get master data
    $masters = file_get_contents("json/newice/newice.json");
    $masters = json_decode($masters, true);
    if (!isset($masters)) {
        echo "empty is json/category/category.json\n";
        exit;
    }
    $datas = $categorys = [];

    foreach ($masters as $key => $master) {
        if (!in_array($master['category'], $categorys)) {
            $categorys[] = $master['category'];
        }
        $datas[$master['category']][] = $master;
    }
    file_put_contents("json/category/category.json", json_encode($categorys, JSON_UNESCAPED_UNICODE));

    foreach ($categorys as $key => $category) {
        // sort array
        $key_id = [];
        foreach ($datas[$category] as $key2 => $data) {
            $key_id[$key2] = $data['release_date'];
        }
        array_multisort($key_id , SORT_DESC , $datas[$category]);
    }

    $limit = 6;
    foreach ($categorys as $key => $category) {
        $pages = [];
        foreach ($datas[$category] as $key2 => $data) {
            $key2 = $key2 + 1;
            $id = (int)ceil($key2 / $limit);
            $pages[$id][] = $data;
        }
        foreach ($pages as $key2 => $page) {
            file_put_contents("json/category/category_{$key}_{$key2}.json", json_encode($page, JSON_UNESCAPED_UNICODE));
        }
    }

} catch (Exception $e) {
    $date = date("Y-m-d H:i:s");
    error_log($date.": ".__FILE__." ".$e->getMessage()."\n", 3, "log/error.log");
}
?>
