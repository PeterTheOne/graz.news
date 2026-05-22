<?php

$pdo = new PDO('sqlite:database.sqlite');
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

$pdo->query('
    CREATE TABLE IF NOT EXISTS newsSites (
        title TEXT,
        site TEXT,
        feed TEXT,
        robots TEXT,
        filter NUMERIC,
        UNIQUE(feed)
    )
');

$pdo->query('
    CREATE TABLE IF NOT EXISTS robots (
        site TEXT UNIQUE,
        link TEXT,
        content TEXT,
        status NUMERIC,
        allowed NUMERIC,
        delay NUMERIC,
        requested INTEGER
    )
');

// Legacy `robots` had no UNIQUE(site), so fetch.php appended a fresh row every
// cron run and ballooned the table. Wipe once if the constraint is missing;
// cron repopulates within two hours.
$robotsSchema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='robots'")->fetchColumn();
if (stripos($robotsSchema, 'UNIQUE') === false) {
    $pdo->exec('
        DROP TABLE robots;
        CREATE TABLE robots (
            site TEXT UNIQUE,
            link TEXT,
            content TEXT,
            status NUMERIC,
            allowed NUMERIC,
            delay NUMERIC,
            requested INTEGER
        )
    ');
}

$pdo->query('
    CREATE TABLE IF NOT EXISTS articles (
        site TEXT,
        title TEXT,
        link TEXT,
        created INTEGER,
        UNIQUE(link)
    )
');

$insertNewsSite = $pdo->prepare('
    INSERT OR REPLACE INTO newsSites (
        title,
        site,
        feed,
        robots,
        filter
    ) VALUES (
        :title,
        :site,
        :feed,
        :robots,
        :filter
    )
');

$insertRobots = $pdo->prepare('
    INSERT OR REPLACE INTO robots (
        site,
        link,
        content,
        status,
        allowed,
        delay,
        requested
    ) VALUES (
        :site,
        :link,
        :content,
        :status,
        :allowed,
        :delay,
        :requested
    )
');

$insertArticle = $pdo->prepare('
    INSERT OR IGNORE INTO articles (
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
