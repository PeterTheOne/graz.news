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
        requested INTEGER,
        UNIQUE(site, content)
    )
');

// `robots` is an append-on-change audit log: one row per (site, content)
// pair, recording the first time we observed each robots.txt revision.
// Legacy databases either had no UNIQUE constraint at all (so fetch.php
// inserted a fresh row every cron) or a UNIQUE(site) constraint that lost
// history on each change. Rebuild if the (site, content) constraint is
// missing, collapsing duplicates by keeping MIN(requested) per pair.
$robotsSchema = $pdo->query("SELECT sql FROM sqlite_master WHERE type='table' AND name='robots'")->fetchColumn();
if (stripos(preg_replace('/\s+/', '', $robotsSchema), 'UNIQUE(site,content)') === false) {
    $pdo->beginTransaction();
    try {
        $pdo->exec('
            CREATE TABLE robots_new (
                site TEXT,
                link TEXT,
                content TEXT,
                status NUMERIC,
                allowed NUMERIC,
                delay NUMERIC,
                requested INTEGER,
                UNIQUE(site, content)
            );
            INSERT INTO robots_new (site, link, content, status, allowed, delay, requested)
                SELECT site, link, content, status, allowed, delay, MIN(requested)
                FROM robots
                GROUP BY site, content;
            DROP TABLE robots;
            ALTER TABLE robots_new RENAME TO robots;
        ');
        $pdo->commit();
    } catch (Throwable $e) {
        $pdo->rollBack();
        throw $e;
    }
}

$pdo->exec('CREATE INDEX IF NOT EXISTS robots_site_requested ON robots(site, requested)');

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
    INSERT OR IGNORE INTO robots (
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
