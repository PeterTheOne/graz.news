<?php
// todo: remove
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'database.inc.php';
require 'functions.inc.php';

define('THEMES_PATH', 'theme/');

$articles = $pdo->query('SELECT * FROM articles ORDER BY created DESC LIMIT 27');

$params = [
    'articles' => $articles
];

echo loadTemplate('index', $params);
