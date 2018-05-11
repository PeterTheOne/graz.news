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
        site TEXT,
        link TEXT,
        content TEXT,
        status NUMERIC,
        allowed NUMERIC,
        delay NUMERIC,
        requested INTEGER
    )
');

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
    INSERT INTO robots (
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
