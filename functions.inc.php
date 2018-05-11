<?php

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

// see: https://stackoverflow.com/a/45676345/782920
function csvToArray ($file_path) {
    $file = fopen($file_path, 'r');
    $headers = fgetcsv($file);

    $data = [];
    while (($row = fgetcsv($file)) !== false)
    {
        $item = [];
        foreach ($row as $key => $value)
            $item[$headers[$key]] = $value ?: null;
        $data[] = $item;
    }
    fclose($file);
    return $data;
}
