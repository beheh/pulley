<?php
$public_feed_groups = array(
	1, // a feed group id you'd like to shared
	2 // another feed group id
);

$email  = 'email@example.com';
$pass   = 'hunter42';
$api_key = md5($email.':'.$pass);

$ch = curl_init();
curl_setopt($ch, CURLOPT_POST, 1);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, 'api_key='.$api_key);

curl_setopt($ch, CURLOPT_URL,'https://fever.example.com/?api&feeds&items&max_id=0');
$result = curl_exec($ch);
$fever = json_decode($result);

$feeds = $fever->{'feeds'};
$feed_objects = array();
$last_update = 0;
foreach($feeds as $feed) {
	$feed_objects[$feed->id] = $feed;
        if($feed->last_updated_on_time > $last_update) {
                $last_update = $feed->last_updated_on_time;
        }
}

$feeds_groups = $fever->{'feeds_groups'};
$public_feeds = array();
foreach($feeds_groups as $feed) {
	if(in_array($feed->{'group_id'}, $public_feed_groups)) {
		$feed_ids = explode(',', $feed->{'feed_ids'});
		foreach($feed_ids as $id) {
			$public_feeds[$id] = $feed_objects[$id];
		}
	}
}

header('Content-Type: application/atom+xml; charset=utf-8');
if($last_update > 0) header('Last-Modified: '.gmdate('D, d M Y H:i:s', $last_update).' GMT');

if($_SERVER['REQUEST_METHOD'] === 'HEAD') {
	exit();
}

$url = 'https://'.htmlentities($_SERVER['SERVER_NAME'].$_SERVER['REQUEST_URI']);

echo '<?xml version="1.0" encoding="utf-8"?>'.PHP_EOL;
echo '<feed xmlns="http://www.w3.org/2005/Atom">'.PHP_EOL;
echo "\t".'<title>My Public Feed</title>'.PHP_EOL;
echo "\t".'<link rel="self" href="'.$url.'" />'.PHP_EOL;
echo "\t".'<subtitle>feeding the public</subtitle>'.PHP_EOL;
if($last_update > 0) echo "\t".'<updated>'.date(DATE_W3C, $last_update).'</updated>'.PHP_EOL;	
echo "\t".'<generator>Pulley</generator>'.PHP_EOL;
echo "\t".'<id>'.$url.'</id>'.PHP_EOL;

$items = $fever->{'items'};
foreach($items as $item) {
	$feed_id = $item->{'feed_id'};
	if(isset($public_feeds[$feed_id]) && $public_feeds[$feed_id] == true) {
		$feed = $public_feeds[$feed_id];
		echo "\t".'<entry>'.PHP_EOL;
		echo "\t\t".'<title type="html"><![CDATA['.$item->title.']]></title>'.PHP_EOL;
		echo "\t\t".'<link href="'.htmlentities($item->url).'" />'.PHP_EOL;
		echo "\t\t".'<id>'.htmlentities($item->url).'</id>'.PHP_EOL;
		echo "\t\t".'<content type="html"><![CDATA['.$item->html.']]></content>'.PHP_EOL;
		if($item->author) {
			echo "\t\t".'<author>'.PHP_EOL;
		 	echo "\t\t\t".'<name>'.htmlentities($item->author).'</name>'.PHP_EOL;
			echo "\t\t".'</author>'.PHP_EOL;
		}
		echo "\t\t".'<updated>'.date(DATE_W3C, $item->created_on_time).'</updated>'.PHP_EOL;
		echo "\t\t".'<source>'.PHP_EOL;
		echo "\t\t\t".'<id>'.$feed->url.'</id>'.PHP_EOL;
		echo "\t\t\t".'<title>'.htmlentities($feed->title).'</title>'.PHP_EOL;
		echo "\t\t\t".'<updated>'.date(DATE_W3C, $feed->last_updated_on_time).'</updated>'.PHP_EOL;
		echo "\t\t".'</source>'.PHP_EOL;
		echo "\t".'</entry>'.PHP_EOL;
	}
}

curl_close($ch);

echo '</feed>';
