<?php

require 'vendor/autoload.php';
require 'database.inc.php';
require 'functions.inc.php';

$userAgent = 'GrazNews';
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
    $robotsTxt = file_get_contents($newsSite['robots']);
    $robots = new RobotsTxtParser($robotsTxt);
    $robots->setUserAgent($userAgent);
    //echo $robots->getDelay($userAgent) . "\n";
    //echo $robots->isAllowed($newsSite['feed']) . "\n";
    // todo: do something with robots isAllowed

    $feed = Feed::loadRss($newsSite['feed']);
    foreach ($feed->item as $item) {
        $insertArticle->bindParam(':site', $newsSite['title']);
        $insertArticle->bindParam(':title', $item->title);
        $insertArticle->bindParam(':link', $item->link);
        $insertArticle->bindParam(':created', $item->timestamp);
        $insertArticle->execute();
    }
}

