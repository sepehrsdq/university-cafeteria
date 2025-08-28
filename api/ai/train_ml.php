<?php
// C:\xampp\htdocs\cafeteria_api\ai\train_ml.php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);
ignore_user_abort(true);
set_time_limit(600); // ۱۰ دقیقه

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php'; // JSON/CORS headers
require __DIR__ . '/../db.php';        // $pdo

use Phpml\Regression\SVR;
use Phpml\SupportVectorMachine\Kernel;
use Phpml\ModelManager;

/* ---------- helpers ---------- */
function mealTypeToId($meal): int {
  if (is_numeric($meal)) return (int)$meal;
  $map = ['breakfast'=>0,'lunch'=>1,'dinner'=>2];
  $m = strtolower((string)$meal);
  return $map[$m] ?? 1;
}
function phpDow(string $date): int { $dt = new DateTime($date); return (int)$dt->format('N'); }
function monthNum(string $date): int { $dt = new DateTime($date); return (int)$dt->format('n'); }
function isYmd($s): bool { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s); }
function clamp(float $x, float $lo, float $hi): float { return max($lo, min($hi, $x)); }

/* ---------- read params ---------- */
$raw = file_get_contents('php://input');
$payload = [];
if ($raw) { $j = json_decode($raw, true); if (json_last_error()===JSON_ERROR_NONE && is_array($j)) $payload=$j; }

$get = $_GET;
$param = function(string $k, $default=null) use ($payload,$get){
  return $payload[$k] ?? $get[$k] ?? $default;
};

$cutoff       = $param('cutoff', date('Y-m-d'));
$minDate      = $param('min_date', null);
$allowZero    = (int)$param('allow_zero_init', 1);
$includeInact = (int)$param('include_inactive', 0);
$ignoreMeal   = (int)$param('ignore_meal', 0);

$cutoffDate = isYmd($cutoff) ? $cutoff : date('Y-m-d');
$minDateSql = ($minDate && isYmd($minDate)) ? $minDate : null;

/* ---------- WHERE ---------- */
$where = [];
$params = [':cutoff'=>$cutoffDate];
if (!$allowZero)   $where[] = "d.initial_count > 0";
$where[] = "d.date <= :cutoff";
if ($minDateSql) { $where[] = "d.date >= :min_date"; $params[':min_date'] = $minDateSql; }
$wsql = $where ? ("WHERE ".implode(" AND ", $where)) : "";

/* ---------- SQL ---------- */
$statusList = "('reserved','paid','used','completed')";
$sql = "
SELECT
  d.date,
  d.meal_type,
  d.self_id,
  d.food_id,
  d.initial_count,
  COALESCE(COUNT(r.id),0) AS demand
FROM daily_food_plans d
LEFT JOIN reservations r
  ON r.daily_food_plan_id = d.id
 AND r.status IN $statusList
$wsql
GROUP BY d.date, d.meal_type, d.self_id, d.food_id, d.initial_count
ORDER BY d.date ASC
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

if (!$rows) {
  echo json_encode([
    'status'=>'ok','samples'=>0,
    'message'=>'No rows with current filters',
    'filters_used'=>[
      'cutoff'=>$cutoffDate,'min_date'=>$minDateSql,
      'allow_zero_init'=>$allowZero,'include_inactive'=>$includeInact,
      'ignore_meal'=>$ignoreMeal
    ]
  ], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ---------- build features ---------- */
$samples=[]; $targetsRate=[]; $allInitC=[]; $allDemand=[];
$historyByCombo=[]; $byComboByDOW=[];

foreach ($rows as $r) {
  $date   = (string)$r['date'];
  $mealId = mealTypeToId($r['meal_type']);
  if ($ignoreMeal) $mealId = 99;
  $selfId = (int)$r['self_id'];
  $foodId = (int)$r['food_id'];
  $initC  = (int)$r['initial_count'];
  $demand = (int)$r['demand'];

  $dow   = phpDow($date);
  $month = monthNum($date);
  $combo = $mealId.'-'.$selfId.'-'.$foodId;

  $past = $historyByCombo[$combo] ?? [];
  $lastDemand = $past ? end($past) : 0.0;
  $mean7 = 0.0;
  if ($past) { $tail = array_slice($past, -7); $mean7 = array_sum($tail)/count($tail); }

  $same = $byComboByDOW[$combo][$dow] ?? [];
  $sameDowMean = $same ? (array_sum($same)/count($same)) : 0.0;

  $features = [(int)$dow,(int)$month,(int)$mealId,(int)$selfId,(int)$foodId,(int)$initC,(float)$lastDemand,(float)$mean7,(float)$sameDowMean];
  $samples[]    = $features;
  $allInitC[]   = $initC;
  $allDemand[]  = $demand;

  $rate = ($initC > 0) ? ($demand / $initC) : 0.0;
  $targetsRate[] = clamp($rate, 0.0, 1.5);

  $historyByCombo[$combo][] = $demand;
  if (!isset($byComboByDOW[$combo][$dow])) $byComboByDOW[$combo][$dow]=[];
  $byComboByDOW[$combo][$dow][] = $demand;
}

/* ---------- MinMax scaling ---------- */
$dim = 9;
$mins = array_fill(0,$dim, INF);
$maxs = array_fill(0,$dim,-INF);
foreach ($samples as $f) {
  for ($j=0;$j<$dim;$j++) {
    if ($f[$j] < $mins[$j]) $mins[$j] = $f[$j];
    if ($f[$j] > $maxs[$j]) $maxs[$j] = $f[$j];
  }
}
$ranges = [];
for ($j=0;$j<$dim;$j++) { $ranges[$j] = $maxs[$j]-$mins[$j]; if ($ranges[$j]==0.0) $ranges[$j] = 1.0; }
$scale = function(array $f) use($mins,$ranges,$dim) {
  for ($j=0;$j<$dim;$j++) { $f[$j] = ($f[$j]-$mins[$j])/$ranges[$j]; }
  return $f;
};
$samplesScaled = array_map($scale, $samples);

/* ---------- split ---------- */
$N = count($samplesScaled);
$trainN = max(1, min((int)floor($N*0.8), $N-1));
$trainX = array_slice($samplesScaled, 0, $trainN);
$trainY = array_slice($targetsRate,   0, $trainN);
$testX  = array_slice($samplesScaled, $trainN);
$testInitC = array_slice($allInitC,   $trainN);
$testYTrue = array_slice($allDemand,  $trainN);

/* ---------- metrics ---------- */
$metrics = function(array $yT, array $yP) {
  $n = max(1, count($yT));
  $sse=0.0; $sumAbs=0.0; $sumTrue=0.0; $sumPct=0.0; $cnt=0;
  for ($i=0;$i<count($yP);$i++) {
    $t=(float)$yT[$i]; $p=(float)$yP[$i];
    $d=$t-$p; $sse+=$d*$d; $sumAbs+=abs($d); $sumTrue+=abs($t);
    if ($t!=0.0) { $sumPct += abs($d/$t); $cnt++; }
  }
  $rmse = sqrt($sse/$n);
  $mape = $cnt ? 100.0*$sumPct/$cnt : null;
  $wape = $sumTrue>0 ? 100.0*$sumAbs/$sumTrue : null;
  return [$rmse,$mape,$wape];
};

/* ---------- train grid (ساده‌شده) ---------- */
$bestModel=null; $bestScore=INF;
$best=['C'=>null,'epsilon'=>null,'gamma'=>null,'rmse'=>null,'mape'=>null,'wape'=>null];

$gridC = [1000, 3000];
$gridE = [0.1];
$gridG = [0.01, 0.05, 0.1];

foreach ($gridC as $C) foreach ($gridE as $E) foreach ($gridG as $G) {
  $svr = new SVR(Kernel::RBF, $C, $E, $G);
  $svr->train($trainX, $trainY);

  $pred = [];
  for ($i=0; $i<count($testX); $i++) {
    $rate = (float)$svr->predict($testX[$i]);
    $rate = clamp($rate, 0.0, 1.5);
    $pred[] = max(0.0, $rate * max(1.0, (float)$testInitC[$i]));
  }

  [$rmse,$mape,$wape] = $metrics($testYTrue, $pred);
  $score = $wape ?? ($mape ?? INF);
  if ($score < $bestScore) {
    $bestScore = $score; $bestModel = $svr;
    $best = ['C'=>$C,'epsilon'=>$E,'gamma'=>$G,'rmse'=>$rmse,'mape'=>$mape,'wape'=>$wape];
  }
}

if (!$bestModel) {
  http_response_code(500);
  echo json_encode(['status'=>'error','message'=>'SVR training failed'], JSON_UNESCAPED_UNICODE);
  exit;
}

/* ---------- save ---------- */
$modelsDir = __DIR__.'/../models';
if (!is_dir($modelsDir)) @mkdir($modelsDir,0777,true);
$manager = new ModelManager();
$manager->saveToFile($bestModel, $modelsDir.'/food_predictor.model');

file_put_contents($modelsDir.'/scaler.json', json_encode(['min'=>$mins,'max'=>$maxs]));
file_put_contents($modelsDir.'/model_meta.json', json_encode([
  'ignore_meal'=>$ignoreMeal,
  'trained_at'=>date('c'),
  'target'=>'rate',
  'rate_clip_max'=>1.5,
  'status_used'=>['reserved','paid','used','completed'],
  'filters'=>[
    'cutoff'=>$cutoffDate,
    'min_date'=>$minDateSql,
    'allow_zero_init'=>$allowZero,'include_inactive'=>$includeInact
  ],
  'features'=>'[dow, month, mealId, selfId, foodId, initCount, lastDemand, mean7, sameDowMean]'
], JSON_UNESCAPED_UNICODE));

/* ---------- output ---------- */
echo json_encode([
  'status'=>'ok',
  'samples'=>$N,
  'train'=>$trainN,
  'chosen'=>[
    'algorithm'=>'SVR_RBF',
    'params'=>['C'=>$best['C'],'epsilon'=>$best['epsilon'],'gamma'=>$best['gamma']],
    'rmse'=>$best['rmse'],'mape'=>$best['mape'],'wape'=>$best['wape']
  ],
  'filters_used'=>[
    'cutoff'=>$cutoffDate,'min_date'=>$minDateSql,
    'allow_zero_init'=>$allowZero,'include_inactive'=>$includeInact,
    'ignore_meal'=>$ignoreMeal
  ]
], JSON_UNESCAPED_UNICODE);
