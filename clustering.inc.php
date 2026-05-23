<?php
declare(strict_types=1);

// Article clustering primitives, shared between the evaluation harness and
// (eventually) the index page. Requires wamania/php-stemmer via composer.

use Wamania\Snowball\StemmerFactory;

const STOPWORDS = [
    'aber','alle','allem','allen','aller','alles','als','also','am','an',
    'ander','andere','anderem','anderen','anderer','anderes','anderm','andern',
    'anders','auch','auf','aus','bei','bin','bis','bist','da','damit','dann',
    'der','den','des','dem','die','das','dass','dasselbe','dazu','dein',
    'deine','deinem','deinen','deiner','deines','denn','derer','dessen',
    'dich','dir','du','dies','diese','diesem','diesen','dieser','dieses',
    'doch','dort','durch','ein','eine','einem','einen','einer','eines',
    'einig','einige','einigem','einigen','einiger','einiges','einmal','er',
    'ihn','ihm','es','etwas','euer','eure','eurem','euren','eurer','eures',
    'für','gegen','gewesen','hab','habe','haben','hat','hatte','hatten',
    'hier','hin','hinter','ich','mich','mir','ihr','ihre','ihrem','ihren',
    'ihrer','ihres','euch','im','in','indem','ins','ist','jede','jedem',
    'jeden','jeder','jedes','jene','jenem','jenen','jener','jenes','jetzt',
    'kann','kein','keine','keinem','keinen','keiner','keines','können',
    'könnte','machen','man','manche','manchem','manchen','mancher','manches',
    'mein','meine','meinem','meinen','meiner','meines','mit','muss','musste',
    'nach','nicht','nichts','noch','nun','nur','ob','oder','ohne','sehr',
    'sein','seine','seinem','seinen','seiner','seines','selbst','sich','sie',
    'ihnen','sind','so','solche','solchem','solchen','solcher','solches',
    'soll','sollte','sondern','sonst','über','um','und','uns','unsere',
    'unserem','unseren','unser','unseres','unter','viel','vom','von','vor',
    'während','war','waren','warst','was','weg','weil','weiter','welche',
    'welchem','welchen','welcher','welches','wenn','werde','werden','wie',
    'wieder','will','wir','wird','wirst','wo','wollen','wollte','würde',
    'würden','zu','zum','zur','zwar','zwischen',
    'the','a','an','and','or','of','to','in','on','at','for','with','by',
    'is','are','was','were','this','that','from','as','it','be',
];

function getGermanStemmer() {
    static $stemmer = null;
    if ($stemmer === null) {
        $stemmer = StemmerFactory::create('de');
    }
    return $stemmer;
}

function normalizeGerman(string $s): array {
    $stemmer = getGermanStemmer();
    $s = strip_tags($s);
    $s = html_entity_decode($s, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    $s = mb_strtolower($s);
    $s = preg_replace('/[^\p{L}\p{N}\s]+/u', ' ', $s);
    $tokens = preg_split('/\s+/u', trim($s), -1, PREG_SPLIT_NO_EMPTY);
    $tokens = array_diff($tokens, STOPWORDS);
    $tokens = array_map(fn($t) => $stemmer->stem($t), $tokens);
    // Fold umlauts AFTER stemming so "Bürger" and "Buerger" collide.
    $tokens = array_map(
        fn($t) => strtr($t, ['ä'=>'ae','ö'=>'oe','ü'=>'ue','ß'=>'ss']),
        $tokens
    );
    $tokens = array_filter($tokens, fn($t) => mb_strlen($t) >= 3);
    return array_values(array_unique($tokens));
}

function jaccard(array $a, array $b): float {
    if (!$a || !$b) return 0.0;
    $fa = array_flip($a);
    $fb = array_flip($b);
    $inter = count(array_intersect_key($fa, $fb));
    $union = count($fa + $fb);
    return $union > 0 ? $inter / $union : 0.0;
}

function timeDecay(int $secondsApart): float {
    $hours = $secondsApart / 3600;
    if ($hours <= 6)   return 1.0;
    if ($hours <= 24)  return 0.8;
    if ($hours <= 72)  return 0.5;
    if ($hours <= 168) return 0.2;
    return 0.0;
}

function pairScore(
    array $titleTokensA, array $titleTokensB,
    array $descTokensA,  array $descTokensB,
    int $secondsApart,
    float $titleWeight = 0.7,
    float $descWeight  = 0.3
): float {
    $td = timeDecay($secondsApart);
    if ($td === 0.0) return 0.0;

    $titleSim = jaccard($titleTokensA, $titleTokensB);

    // Title-only fallback when either side has no description, so the blend
    // isn't penalized by a forced zero on the description term.
    if (empty($descTokensA) || empty($descTokensB)) {
        return $titleSim * $td;
    }
    $descSim = jaccard($descTokensA, $descTokensB);
    return ($titleWeight * $titleSim + $descWeight * $descSim) * $td;
}

final class UnionFind {
    private array $parent;
    public function __construct(int $n) { $this->parent = range(0, $n - 1); }
    public function find(int $x): int {
        while ($this->parent[$x] !== $x) {
            $this->parent[$x] = $this->parent[$this->parent[$x]];
            $x = $this->parent[$x];
        }
        return $x;
    }
    public function union(int $a, int $b): void {
        $ra = $this->find($a); $rb = $this->find($b);
        if ($ra !== $rb) $this->parent[$ra] = $rb;
    }
}
