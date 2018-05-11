<?php
// todo: remove
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'database.inc.php';
require 'functions.inc.php';

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
