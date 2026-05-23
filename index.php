<?php

require_once 'vendor/autoload.php';
require_once 'database.inc.php';
require_once 'functions.inc.php';
//require_once 'fetch.php';

define('THEMES_PATH', 'theme/');

$articles = $pdo->query('
  SELECT * FROM articles
  WHERE site IN (
    SELECT r1.site FROM robots r1
    WHERE r1.allowed = 1
    AND r1.requested = (SELECT MAX(r2.requested) FROM robots r2 WHERE r2.site = r1.site)
  )
  ORDER BY created DESC LIMIT 27
')->fetchAll();

$params = [
    'articles' => $articles
];

echo loadTemplate('index', $params);
