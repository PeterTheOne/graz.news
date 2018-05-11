<?php

require_once 'vendor/autoload.php';
require_once 'database.inc.php';
require_once 'functions.inc.php';
//require_once 'fetch.php';

define('THEMES_PATH', 'theme/');

$articles = $pdo->query('
  SELECT * FROM articles
  WHERE site IN (
    SELECT site FROM (
      SELECT site, allowed FROM robots GROUP BY site ORDER BY requested DESC
    ) WHERE allowed = 1
  )
  ORDER BY created DESC LIMIT 27
')->fetchAll();

$params = [
    'articles' => $articles
];

echo loadTemplate('index', $params);
