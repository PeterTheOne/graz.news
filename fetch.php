<?php

require 'vendor/autoload.php';
require 'database.inc.php';

$userAgent = 'GrazNews';

$newsSites = [
    [
        'title' => 'Stadt Graz',
        'site' => 'https://www.graz.at/',
        'feed' => 'https://www.graz.at/cms/ziel/8345527/DE/',
        'robots' => 'https://www.graz.at/robots.txt',
        'filter' => false
    ],
    [
        'title' => 'Futter',
        'site' => 'http://futter.kleinezeitung.at/',
        'feed' => 'http://futter.kleinezeitung.at/tag/graz/feed/',
        'robots' => 'http://futter.kleinezeitung.at/robots.txt',
        'filter' => false
    ]
];

$insertArticle = $pdo->prepare('
    INSERT OR IGNORE INTO
     articles (
        site,
        title,
        link,
        created
    ) VALUES (
        :site,
        :title,
        :link,
        :created
    )
');


foreach($newsSites as $newsSite) {
    $robots = new RobotsTxtParser(file_get_contents($newsSite['robots']));
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

