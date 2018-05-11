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
function csvToArray($file_path) {
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

function timestampToPrettyDate($timestamp) {
    $datetime = (new DateTime())->setTimestamp((int) $timestamp);
    $interval = (new DateTime())->diff($datetime);
    if ($datetime > (new DateTime())->sub(new DateInterval('PT1M'))) {
        $n = $interval->format('%s');
        return 'vor ' . $n . ($n <= 1 ? ' Sekunde' : ' Sekunden');
    }
    if ($datetime > (new DateTime())->sub(new DateInterval('PT1H'))) {
        $n = $interval->format('%i');
        return 'vor ' . $n . ($n <= 1 ? ' Minute' : ' Minuten');
    }
    if ($datetime > (new DateTime())->sub(new DateInterval('P1D'))) {
        $n = $interval->format('%h');
        return 'vor ' . $n . ($n <= 1 ? ' Stunde' : ' Stunden');
    }
    if ($datetime > (new DateTime())->sub(new DateInterval('P7D'))) {
        $n = $interval->format('%d');
        return 'vor ' . $n . ($n <= 1 ? ' Tag' : ' Tagen');
    }
    return date_format((new DateTime())->setTimestamp((int) $timestamp), 'd.m.Y H:i:s');
}
