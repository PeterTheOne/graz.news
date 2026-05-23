<?php

require_once 'vendor/autoload.php';
require_once 'database.inc.php';
require_once 'functions.inc.php';
require_once 'clustering.inc.php';
//require_once 'fetch.php';

define('THEMES_PATH', 'theme/');

const CLUSTER_THRESHOLD = 0.30;
const CLUSTER_WINDOW_DAYS = 7;
const CLUSTERS_TO_SHOW = 27;

// Time decay zeroes pair scores past 7 days, so a wider pool inflates work
// without changing groupings.
$stmt = $pdo->prepare('
    SELECT * FROM articles
    WHERE site IN (
        SELECT r1.site FROM robots r1
        WHERE r1.allowed = 1
        AND r1.requested = (SELECT MAX(r2.requested) FROM robots r2 WHERE r2.site = r1.site)
    )
    AND created >= :since
    ORDER BY created DESC
');
$stmt->execute([':since' => time() - CLUSTER_WINDOW_DAYS * 86400]);
$articles = $stmt->fetchAll();

$n = count($articles);
$titleTokens = [];
$descTokens  = [];
foreach ($articles as $i => $a) {
    $titleTokens[$i] = normalizeGerman((string)($a->title ?? ''));
    $descTokens[$i]  = normalizeGerman((string)($a->description ?? ''));
}

$uf = new UnionFind($n);
for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        $score = pairScore(
            $titleTokens[$i], $titleTokens[$j],
            $descTokens[$i],  $descTokens[$j],
            abs((int)$articles[$i]->created - (int)$articles[$j]->created)
        );
        if ($score >= CLUSTER_THRESHOLD) {
            $uf->union($i, $j);
        }
    }
}

$buckets = [];
for ($i = 0; $i < $n; $i++) {
    $buckets[$uf->find($i)][] = $articles[$i];
}
foreach ($buckets as &$items) {
    usort($items, fn($a, $b) => (int)$b->created <=> (int)$a->created);
}
unset($items);

$clusters = array_values($buckets);
usort($clusters, fn($a, $b) => (int)$b[0]->created <=> (int)$a[0]->created);
$clusters = array_slice($clusters, 0, CLUSTERS_TO_SHOW);

$params = [
    'clusters' => $clusters
];

echo loadTemplate('index', $params);
