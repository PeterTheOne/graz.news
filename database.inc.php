<?php

$pdo = new PDO('sqlite:database.sqlite');
$pdo->setAttribute(\PDO::ATTR_ERRMODE, \PDO::ERRMODE_EXCEPTION);
$pdo->setAttribute(\PDO::ATTR_DEFAULT_FETCH_MODE, \PDO::FETCH_OBJ);

$pdo->query('
    CREATE TABLE IF NOT EXISTS articles (
        site TEXT,
        title TEXT,
        link TEXT,
        created INTEGER,
        UNIQUE(link)
    )
');
