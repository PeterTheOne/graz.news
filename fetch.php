<?php

require_once 'vendor/autoload.php';
require_once 'database.inc.php';
require_once 'functions.inc.php';

$userAgent = 'Graz.News';
$newsSitesCsvPath = 'news-sites.csv';

$newsSites = csvToArray($newsSitesCsvPath);

foreach ($newsSites as $newsSite) {
    $filter = $newsSite['filter'] === 'TRUE' ? true : false;

    $insertNewsSite->bindParam(':title', $newsSite['title']);
    $insertNewsSite->bindParam(':site', $newsSite['site']);
    $insertNewsSite->bindParam(':feed', $newsSite['feed']);
    $insertNewsSite->bindParam(':robots', $newsSite['robots']);
    $insertNewsSite->bindParam(':filter', $filter, PDO::PARAM_BOOL);
    $insertNewsSite->execute();
}

foreach ($newsSites as $newsSite) {
    $options = array(
        'http'=>array(
            'method'=>"GET",
            'header'=>
                "User-Agent: " . $userAgent . "\r\n"
        )
    );
    $context = stream_context_create($options);

    $cacheFile = './cache/' . $newsSite['title'] . '-robots.txt';
    if (file_exists($cacheFile)) {
        $cacheDate = (new DateTime())->setTimestamp(filemtime($cacheFile));
        if ((new DateTime())->diff($cacheDate)->format('%i') < 30) {
            continue;
        }
    }

    $robotsContent = file_get_contents($newsSite['robots'], false, $context);
    file_put_contents($cacheFile, $robotsContent);
    $robots = new RobotsTxtParser($robotsContent);
    $robots->setUserAgent($userAgent);
    $allowed =  $robots->isAllowed($newsSite['feed']);
    $delay = $robots->getDelay($userAgent);
    $requested = (new DateTime())->getTimestamp();

    $insertRobots->bindParam(':site', $newsSite['title']);
    $insertRobots->bindParam(':link', $newsSite['robots']);
    $insertRobots->bindParam(':content', $robotsContent);
    //$insertRobots->bindParam(':status', );
    $insertRobots->bindParam(':allowed', $allowed, PDO::PARAM_BOOL);
    $insertRobots->bindParam(':delay', $delay);
    $insertRobots->bindParam(':requested', $requested);
    $insertRobots->execute();

    if (!$allowed) {
        continue;
    }

    // todo: do something with delay
    // todo: set bot name in request

    $feed = new SimplePie();
    $feed->set_feed_url($newsSite['feed']);
    $feed->set_cache_location('./cache');
    $feed->set_useragent($userAgent);
    $feed->init();

    foreach ($feed->get_items() as $item) {
        $title = $item->get_title();
        $description = $item->get_description();
        $link = $item->get_permalink();
        $timestamp = $item->get_date('U');


        $relevance = true;
        if ($newsSite['filter'] === 'TRUE') {
            //echo $newsSite['site'] . "\n";
            //echo $item->title . "\n";
            //echo $item->description . "\n";
            $relevance = stripos($title . $description . $link, 'graz') !== FALSE;
            //echo ($relevance ? 'relevant' : 'not relevant') . "\n";
        }
        if (!$relevance) {
            continue;
        }

        $insertArticle->bindParam(':site', $newsSite['title']);
        $insertArticle->bindParam(':title', $title);
        $insertArticle->bindParam(':link', $link);
        $insertArticle->bindParam(':created', $timestamp);
        $insertArticle->execute();
    }
}
