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
        echo "empty is json/newice/newice.json\n";
        // exit;
    }

    date_default_timezone_set('Asia/Tokyo');

    $client = new Client();
    // $url = "https://mognavi.jp/icecream/newitem/01/60";
    // $url = "https://mognavi.jp/icecream/newitem/01/40";
    // $url = "https://mognavi.jp/icecream/newitem/01/20";
    $url = "https://mognavi.jp/icecream/newitem";

    $crawler = $client->request('GET', $url);

    $names = $crawler->filter('#mainCol > div > div.newfood_area > ul > a > li > div.text.non-clickable > dl > dt')->each(function($element){
        $data = [];
        $data['name'] = trim($element->text());
        $data['create'] = date("Y-m-d H:i:s");
        return $data;
    });
    $titles = $crawler->filter('#mainCol > div > div.newfood_area > ul > a > li > div.text.non-clickable > dl > dd.prTitle')->each(function($element){
        $data = [];
        $data['title'] = trim($element->text());
        return $data;
    });
    $subtexts = $crawler->filter('#mainCol > div > div.newfood_area > ul > a > li > div.text.non-clickable > dl > dd.prDetail')->each(function($element){
        $data = [];
        $data['subtexts'] = trim($element->text());
        return $data;
    });
    $links = $crawler->filter('#mainCol > div > div.newfood_area > ul > a')->each(function($element){
        $data = [];
        $data['link'] = "https://mognavi.jp".trim($element->attr('href'));

        $client2 = new Client();
        $crawler2 = $client2->request('GET', $data['link']);
        $texts = $crawler2->filter('#pInfo > div:nth-child(4)')->each(function($element){
            return trim($element->text());
        });
        // $categorys = $crawler2->filter('td.category')->each(function($element){
        //     return trim($element->text());
        // });
        $tables = $crawler2->filter('#pInfo > table:nth-child(6) > tr > td')->each(function($element){
            return trim($element->text());
        });

        $data['category'] = empty($tables[0]) == true ? "":$tables[0];
        $data['capacity'] = empty($tables[1]) == true ? "":$tables[1];
        $data['maker'] = empty($tables[2]) == true ? "":$tables[2];
        $data['kcal'] = empty($tables[3]) == true ? "":$tables[3];
        $data['brand'] = empty($tables[4]) == true ? "":$tables[4];
        $data['price'] = empty($tables[5]) == true ? "":$tables[5];
        // $data['release'] = empty($tables[6]) == true ? "":$tables[6];
        $data['jan'] = empty($tables[7]) == true ? "":$tables[7];
        $data['text'] = empty($texts[0]) == true ? "":$texts[0];


        // $data['brand'] = str_replace('\r', '', $data['brand']);
        // $data['brand'] = str_replace('\t', '', $data['brand']);
        // $data['brand'] = str_replace('\n', '', $data['brand']);
        return $data;
    });
    $srcs = $crawler->filter('#mainCol > div > div.newfood_area > ul > a > li > div.foodph > img')->each(function($element){
        $data = [];
        $data['src'] = str_replace("120x90", "500x300", trim($element->attr('src')));
        return $data;
    });
    $releases = $crawler->filter('#mainCol > div > div.newfood_area > ul > a > li > div.foodph > p')->each(function($element){
        $data = [];
        $date = trim($element->text());
        $data['release'] = $date;

        $month = explode("月", $date);
        $day = explode("日", $month[1]);
        $month = str_pad($month[0], 2, 0, STR_PAD_LEFT);
        $day = str_pad($day[0], 2, 0, STR_PAD_LEFT);
        $release_date = date("Y").'-'.$month.'-'.$day.' 00:00:00';
        $data['release_date'] = $release_date;
        return $data;
    });

    for ($i=0; $i < count($names); $i++) {
        if (empty($titles[$i])) continue;
        if (empty($names[$i])) continue;
        if (empty($subtexts[$i])) continue;
        if (empty($links[$i])) continue;
        if (empty($srcs[$i])) continue;
        if (empty($releases[$i])) continue;

        if (!empty($titles[$i])) {
            $datas[$i] = array_merge($names[$i], $titles[$i]);
        }
        if (!empty($subtexts[$i])) {
            $datas[$i] = array_merge($datas[$i], $subtexts[$i]);
        }
        if (!empty($links[$i])) {
            $datas[$i] = array_merge($datas[$i], $links[$i]);
        }
        if (!empty($srcs[$i])) {
            $datas[$i] = array_merge($datas[$i], $srcs[$i]);
        }
        if (!empty($releases[$i])) {
            $datas[$i] = array_merge($datas[$i], $releases[$i]);
        }
    }

    // 新着データがあるか調べる
    foreach ($datas as $key => $data) {
        if (empty($data['text'])) {
            $data['text'] = $data['subtexts'];
        }
        unset($data['subtexts']);

        $check = ['link'=>false, 'name'=>false];

        foreach ($masters as $key2 => $master) {

            if ($data['link'] == $master['link']) {
                $check['link'] = true;
            }

            if ($data['name'] == $master['name']) {
                $check['name'] = true;
            }
        }
        if ($check['name'] == false && $check['link'] == false) {
            $masters[] = $data;
            // $ret = chatwork(var_export($data, true));

            $payload['title'] = "新着アイス[".$data['name']."]が公開されました。";
            $payload['text'] = $data['name']."\n".$data['text']."\n".$data['link'];
            // $ret2 = nifty_push($payload, 'newice');

            var_dump($data);
            var_dump($payload);
        }
    }


    // sort array
     $key_id = [];
     foreach ($masters as $key => $master) {
        $key_id[$key] = $master['release_date'];
     }
     array_multisort($key_id , SORT_DESC , $masters);

    file_put_contents("json/newice/newice.json", json_encode($masters, JSON_UNESCAPED_UNICODE));

    $limit = 6;
    $pages = [];
    foreach ($masters as $key => $master) {
        // TODO 一時的
        if (!empty($master['imgsrc'])) {
            $master['src'] = $master['imgsrc'];
        }
        $key = $key + 1;
        $id = (int)ceil($key / $limit);
        $pages[$id][] = $master;
    }
    foreach ($pages as $key => $page) {
        file_put_contents("json/newice/newice_{$key}.json", json_encode($page, JSON_UNESCAPED_UNICODE));
    }

} catch (Exception $e) {
    $date = date("Y-m-d H:i:s");
    error_log($date.": ".__FILE__." ".$e->getMessage()."\n", 3, "log/error.log");
}
?>
