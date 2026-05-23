<?php
declare(strict_types=1);

// Tuning sandbox for the article clustering pipeline. Reads articles from
// the SQLite DB, scores all pairs, and prints histogram + borderline pairs
// + clusters at several thresholds so you can eyeball what works.
//
// Run:  php evaluate-clusters.php
//       WINDOW_DAYS=60 php evaluate-clusters.php

require_once 'vendor/autoload.php';
require_once 'database.inc.php';
require_once 'clustering.inc.php';

// --- Knobs (edit and re-run as you iterate) --------------------------------

$windowDays = (int)(getenv('WINDOW_DAYS') ?: 30);
const TITLE_WEIGHT = 0.7;
const DESC_WEIGHT  = 0.3;
const THRESHOLDS   = [0.20, 0.30, 0.40, 0.50];
const BORDERLINE_CENTER = 0.30;
const BORDERLINE_HALF_WIDTH = 0.10;
const MAX_BORDERLINE_SHOWN = 40;
const MAX_CLUSTERS_SHOWN_PER_THRESHOLD = 8;

// --- Load -------------------------------------------------------------------

$since = time() - $windowDays * 86400;
$stmt = $pdo->prepare('
    SELECT site, title, description, created
    FROM articles
    WHERE created >= :since
    ORDER BY created DESC
');
$stmt->execute([':since' => $since]);
$articles = $stmt->fetchAll();
$n = count($articles);

if ($n < 2) {
    fwrite(STDERR, "Only $n articles in last $windowDays days. Run fetch.php first.\n");
    exit(1);
}

$withDesc = 0;
foreach ($articles as $a) {
    if (!empty($a->description)) $withDesc++;
}
echo "Loaded $n articles from last $windowDays days ($withDesc with description).\n\n";

// --- Tokenize ---------------------------------------------------------------

$titleTokens = [];
$descTokens  = [];
foreach ($articles as $i => $a) {
    $titleTokens[$i] = normalizeGerman((string)($a->title ?? ''));
    $descTokens[$i]  = normalizeGerman((string)($a->description ?? ''));
}

// --- All-pairs scoring (time decay early-exits pairs >7 days apart) --------

$pairs = [];
for ($i = 0; $i < $n; $i++) {
    for ($j = $i + 1; $j < $n; $j++) {
        $score = pairScore(
            $titleTokens[$i], $titleTokens[$j],
            $descTokens[$i],  $descTokens[$j],
            abs((int)$articles[$i]->created - (int)$articles[$j]->created),
            TITLE_WEIGHT, DESC_WEIGHT
        );
        if ($score > 0) {
            $pairs[] = [$i, $j, $score];
        }
    }
}

echo count($pairs) . " non-zero pairs (of " . ($n * ($n - 1) / 2) . " total)\n\n";

// --- Histogram --------------------------------------------------------------

echo "Score distribution (look for a valley — that's roughly where to cut):\n";
$bins = 20;
$h = array_fill(0, $bins, 0);
foreach ($pairs as [, , $s]) {
    $b = min($bins - 1, (int)($s * $bins));
    $h[$b]++;
}
$max = max($h) ?: 1;
for ($b = 0; $b < $bins; $b++) {
    $lo = $b / $bins;
    $bar = str_repeat('#', (int)(60 * $h[$b] / $max));
    printf("  %.2f-%.2f  %5d  %s\n", $lo, $lo + 1 / $bins, $h[$b], $bar);
}
echo "\n";

// --- Borderline pairs (read these — most informative thing in this report) -

echo "Borderline pairs around " . BORDERLINE_CENTER . " (±" . BORDERLINE_HALF_WIDTH . "):\n\n";
$borderline = [];
foreach ($pairs as [$i, $j, $s]) {
    if (abs($s - BORDERLINE_CENTER) <= BORDERLINE_HALF_WIDTH) {
        $borderline[] = [$i, $j, $s];
    }
}
usort($borderline, fn($a, $b) => $b[2] <=> $a[2]);
foreach (array_slice($borderline, 0, MAX_BORDERLINE_SHOWN) as [$i, $j, $s]) {
    $hoursApart = abs((int)$articles[$i]->created - (int)$articles[$j]->created) / 3600;
    printf("  score=%.3f  Δ=%5.1fh\n", $s, $hoursApart);
    printf("    [%s] %s\n", $articles[$i]->site, $articles[$i]->title);
    printf("    [%s] %s\n\n", $articles[$j]->site, $articles[$j]->title);
}

// --- Clusters at multiple thresholds ---------------------------------------

foreach (THRESHOLDS as $t) {
    $uf = new UnionFind($n);
    foreach ($pairs as [$i, $j, $s]) {
        if ($s >= $t) $uf->union($i, $j);
    }
    $groups = [];
    for ($i = 0; $i < $n; $i++) {
        $groups[$uf->find($i)][] = $i;
    }
    $multi = array_filter($groups, fn($g) => count($g) >= 2);
    echo "\n=== Threshold $t: " . count($groups) . " clusters, "
       . count($multi) . " with ≥2 articles ===\n";

    uasort($multi, fn($a, $b) => count($b) <=> count($a));
    $shown = 0;
    foreach ($multi as $g) {
        if ($shown++ >= MAX_CLUSTERS_SHOWN_PER_THRESHOLD) break;
        echo "  Cluster (size " . count($g) . "):\n";
        foreach ($g as $i) {
            $when = date('Y-m-d H:i', (int)$articles[$i]->created);
            printf("    %s [%s] %s\n", $when, $articles[$i]->site, $articles[$i]->title);
        }
    }
}

echo "\n";
