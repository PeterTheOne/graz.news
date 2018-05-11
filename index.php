<?php
// todo: remove
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require 'vendor/autoload.php';
require 'database.inc.php';

define('THEMES_PATH', 'theme/');

function loadTemplate($page, $params, $layout = true) {
    if ($layout) {
        ob_start();
    }
    ob_start();
    extract($params, EXTR_SKIP);
    require_once(THEMES_PATH . '/' . $page . '.php');
    $content = ob_get_clean();
    if ($layout) {
        require_once(THEMES_PATH . '/layout.php');
        return ob_get_clean();
    }
    return $content;
}

$articles = $pdo->query('SELECT * FROM articles ORDER BY created DESC LIMIT 27');

$params = [
    'articles' => $articles
];

echo loadTemplate('index', $params);
