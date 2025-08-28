<?php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php'; // CORS + JSON header
require __DIR__ . '/../db.php';        // $pdo

use Phpml\ModelManager;

/* ---------- helpers ---------- */
function json_error(int $code, string $msg, array $extra = []) {
  http_response_code($code);
  echo json_encode(array_merge(['status'=>'error','message'=>$msg], $extra), JSON_UNESCAPED_UNICODE);
  exit;
}
function validate_date(string $date): bool {
  $dt = DateTime::createFromFormat('Y-m-d', $date);
  return $dt && $dt->format('Y-m-d') === $date;
}
function mealTxt($meal): string {
  if (is_numeric($meal)) { $m=(int)$meal; return $m===0?'breakfast':($m===1?'lunch':'dinner'); }
  $m = strtolower(trim((string)$meal));
  return in_array($m, ['breakfast','lunch','dinner'], true) ? $m : 'lunch';
}
function mealId($meal): int { $t=mealTxt($meal); return $t==='breakfast'?0:($t==='lunch'?1:2); }
function phpDow(string $date): int { $dt=DateTime::createFromFormat('Y-m-d',$date); return $dt?(int)$dt->format('N'):1; }
function monthNum(string $date): int { $dt=DateTime::createFromFormat('Y-m-d',$date); return $dt?(int)$dt->format('n'):1; }
function dowFa(int $phpDow): string {
  // DateTime('N'): 1=Mon ... 7=Sun
  $map=[1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنجشنبه',5=>'جمعه',6=>'شنبه',7=>'یکشنبه'];
  return $map[$phpDow] ?? (string)$phpDow;
}
function clamp(float $x, float $lo, float $hi): float { return max($lo, min($hi, $x)); }

/* ---------- enforce POST ---------- */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error(405,'Method Not Allowed. Use POST.'); }

/* ---------- read body ---------- */
$raw = file_get_contents('php://input');
$payload = [];
if ($raw) {
  $j = json_decode($raw, true);
  if (json_last_error()===JSON_ERROR_NONE && is_array($j)) $payload = $j;
}
if (!$payload) {
  if (!empty($_POST))        $payload = $_POST;
  elseif (!empty($_REQUEST)) $payload = $_REQUEST;
}

/* ---------- params ---------- */
$date     = $payload['date']     ?? null;
$meal     = $payload['meal']     ?? null;
$selfId   = isset($payload['self_id']) ? (int)$payload['self_id'] : null;
$foodId   = isset($payload['food_id']) ? (int)$payload['food_id'] : null;
$foodName = $payload['food_name'] ?? null;
$applyCap = isset($payload['apply_cap']) ? (int)$payload['apply_cap'] : 1;
$explain  = isset($payload['explain'])  ? (int)$payload['explain']  : 0;
$signals  = isset($payload['signals'])  ? (int)$payload['signals']  : 0;

if (!$date || !$meal || $selfId===null || ($foodId===null && !$foodName)) {
  json_error(400, 'params required: date, meal, self_id, and one of (food_id | food_name)', [
    'got_keys' => array_keys($payload)
  ]);
}
if (!validate_date($date)) json_error(400, 'Invalid date format. Use YYYY-MM-DD', ['value'=>$date]);

$meal_text = mealTxt($meal);
$meal_id   = mealId($meal);
$dow       = phpDow($date);
$month     = monthNum($date);

/* ---------- resolve food_id if needed ---------- */
if ($foodId === null) {
  $q = $pdo->prepare("SELECT id FROM foods WHERE name = :n LIMIT 1");
  $q->execute([':n'=>$foodName]);
  $row = $q->fetch(PDO::FETCH_ASSOC);
  if (!$row) json_error(404, 'food_name not found');
  $foodId = (int)$row['id'];
}

/* ---------- load model + meta + scaler ---------- */
$modelPath  = __DIR__ . '/../models/food_predictor.model';
$metaPath   = __DIR__ . '/../models/model_meta.json';
$scalerPath = __DIR__ . '/../models/scaler.json';

if (!file_exists($modelPath)) json_error(500, 'Model not found. Train via ai/train_ml.php first.');
try {
  $manager = new ModelManager();
  /** @var \Phpml\Estimator $model */
  $model = $manager->restoreFromFile($modelPath);
} catch (Throwable $e) {
  json_error(500, 'Failed to load model', ['detail'=>$e->getMessage()]);
}

$meta = [];
if (file_exists($metaPath)) {
  $meta = json_decode((string)file_get_contents($metaPath), true) ?: [];
}
$targetType   = $meta['target'] ?? 'demand'; // 'rate' یا 'demand'
$rateClipMax  = isset($meta['rate_clip_max']) ? (float)$meta['rate_clip_max'] : 1.5;
$ignoreMealMd = (int)($meta['ignore_meal'] ?? 0);
if ($ignoreMealMd === 1) $meal_id = 99; // هم‌راستا با Train

$mins = $maxs = [];
if (file_exists($scalerPath)) {
  $sc = json_decode((string)file_get_contents($scalerPath), true);
  if (isset($sc['min']) && isset($sc['max']) && is_array($sc['min']) && is_array($sc['max'])) {
    $mins = $sc['min']; $maxs = $sc['max'];
  }
}
$scaleFeatures = function(array $f) use($mins,$maxs) {
  if (!$mins || !$maxs) return $f; // اگر scaler نبود، همون خام
  $dim = count($f);
  $out = $f;
  for ($j=0;$j<$dim;$j++) {
    $min = (float)($mins[$j] ?? 0.0);
    $max = (float)($maxs[$j] ?? 1.0);
    $range = ($max - $min);
    if ($range == 0.0) $range = 1.0;
    $out[$j] = ($f[$j] - $min) / $range;
  }
  return $out;
};

/* ---------- build signals (past-only) ---------- */
try {
  // وضعیت‌های معتبر رزرو (در Train هم همین‌ها را شمردیم)
  $statusList = "('reserved','paid','used','completed')";

  // تاریخچهٔ همین ترکیب تا قبل از تاریخ هدف
  $histSql = "
    SELECT d.date AS ddate, COUNT(r.id) AS demand
    FROM daily_food_plans d
    LEFT JOIN reservations r
      ON r.daily_food_plan_id = d.id
     AND r.status IN $statusList
    WHERE d.meal_type = :meal
      AND d.self_id   = :self
      AND d.food_id   = :food
      AND d.date      < :date
    GROUP BY d.date
    ORDER BY d.date ASC
  ";
  $st = $pdo->prepare($histSql);
  $st->execute([':meal'=>$meal_text, ':self'=>$selfId, ':food'=>$foodId, ':date'=>$date]);
  $hist = $st->fetchAll(PDO::FETCH_ASSOC) ?: [];

  $lastDemand = 0.0; $mean7 = 0.0; $sameDowMean = 0.0;
  if ($hist) {
    $demands = []; $same = [];
    foreach ($hist as $h) {
      $d = (int)$h['demand']; $demands[] = $d;
      if (phpDow($h['ddate']) === $dow) $same[] = $d;
    }
    $lastDemand = (float)end($demands);
    $tail = array_slice($demands, -7);
    if ($tail) $mean7 = array_sum($tail)/count($tail);
    if ($same) $sameDowMean = array_sum($same)/count($same);
  }

  // ظرفیت مرجع روز هدف (اگر ساخته شده)
  $capVal   = null; // برای سقف
  $initFeat = 0;    // برای فیچر

  $capSql = "
    SELECT initial_count
    FROM daily_food_plans
    WHERE date=:date AND meal_type=:meal AND self_id=:self AND food_id=:food
    ORDER BY id DESC LIMIT 1
  ";
  $sc = $pdo->prepare($capSql);
  $sc->execute([':date'=>$date, ':meal'=>$meal_text, ':self'=>$selfId, ':food'=>$foodId]);
  if ($rc = $sc->fetch(PDO::FETCH_ASSOC)) {
    if ($rc['initial_count'] !== null) {
      $initFeat = (int)$rc['initial_count'];
      $capVal   = $initFeat;
    }
  }

  // اگر هنوز برنامهٔ روز هدف ساخته نشده: initFeat ← میانگین ظرفیت‌های گذشته (فقط برای فیچر)
  if ($initFeat === 0) {
    $typSql = "
      SELECT ROUND(AVG(initial_count)) AS typical_init
      FROM daily_food_plans
      WHERE meal_type=:meal AND self_id=:self AND food_id=:food AND initial_count > 0
    ";
    $stt = $pdo->prepare($typSql);
    $stt->execute([':meal'=>$meal_text, ':self'=>$selfId, ':food'=>$foodId]);
    if ($rt = $stt->fetch(PDO::FETCH_ASSOC)) {
      if ($rt['typical_init'] !== null) {
        $initFeat = (int)$rt['typical_init'];
        // می‌توان capVal را هم همین قرار داد؛ ترجیحاً فقط اگر apply_cap=1 بخواهی سخت‌گیر باشی.
        // $capVal = $initFeat;
      }
    }
  }

} catch (Throwable $e) {
  json_error(500, 'DB error while building features', ['detail'=>$e->getMessage()]);
}

/* ---------- prepare features (9) + scale ---------- */
$rawFeatures = [
  (int)$dow,
  (int)$month,
  (int)$meal_id,
  (int)$selfId,
  (int)$foodId,
  (int)$initFeat,     // ظرفیت مرجعِ فیچر
  (float)$lastDemand,
  (float)$mean7,
  (float)$sameDowMean
];
$features = $scaleFeatures($rawFeatures);

/* ---------- predict ---------- */
try {
  $predRaw = (float)$model->predict($features);

  if ($targetType === 'rate') {
    // مدل «نرخ تقاضا» را خروجی می‌دهد → به «عدد» تبدیل شود
    $rate = clamp($predRaw, 0.0, $rateClipMax);
    // مرجع ضرب: اگر capVal موجود است، اولویت با آن (روز هدف). وگرنه از initFeat استفاده کن.
    $refInit = ($capVal !== null && $capVal > 0) ? (float)$capVal : (float)$initFeat;
    $refInit = max(1.0, $refInit);
    $yhat = $rate * $refInit;
  } else {
    // مدل مستقیماً «عدد تقاضا» می‌دهد
    $yhat = max(0.0, $predRaw);
  }
} catch (Throwable $e) {
  json_error(500, 'Prediction failed', ['detail'=>$e->getMessage()]);
}

/* ---------- پس‌پردازش: کف نرم + سقف ---------- */
// کف نرم: ترکیب mean7 و sameDowMean (اگر نبود، از هرکدام که هست)
$floorSoft = 0.0;
if ($mean7 > 0 && $sameDowMean > 0) {
  $floorSoft = 0.5 * $mean7 + 0.5 * $sameDowMean;
} elseif ($mean7 > 0) {
  $floorSoft = $mean7;
} elseif ($sameDowMean > 0) {
  $floorSoft = $sameDowMean;
}
$floorSoft = max(1.0, floor($floorSoft * 0.7)); // 70%

$yhat = max($yhat, $floorSoft);

// سقف
if ($applyCap && $capVal !== null && $capVal > 0) {
  $yhat = min($yhat, (float)$capVal);
}

// رُندکردن نهایی
$yhat = (int)max(0, round($yhat));

/* ---------- build response ---------- */
$response = [
  'status' => 'ok',
  'result' => [
    'suggested_capacity' => $yhat
  ],
  'input'  => [
    'date'     => $date,
    'weekday'  => dowFa($dow),
    'meal'     => $meal_text,
    'self_id'  => $selfId,
    'food_id'  => $foodId
  ]
];

// توضیح کوتاه
if ($explain) {
  $parts = [];
  if ($mean7 > 0)       $parts[] = 'میانگین ۷ حضور اخیر ≈ ' . (int)round($mean7);
  if ($sameDowMean > 0) $parts[] = 'میانگین همین روز هفته ≈ ' . (int)round($sameDowMean);
  if ($lastDemand > 0)  $parts[] = 'آخرین دفعه = ' . (int)$lastDemand;
  if ($initFeat > 0)    $parts[] = 'ظرفیت مرجع = ' . (int)$initFeat;
  if ($applyCap && $capVal !== null) $parts[] = 'سقف اعمال شد: ' . (int)$capVal;

  $response['explain'] = 'با توجه به ' . implode('، ', $parts) . '، ظرفیت پیشنهادی: ' . $yhat;
}

// سیگنال‌ها
if ($signals) {
  $response['signals'] = [
    'lastDemand'   => $lastDemand,
    'mean7'        => $mean7,
    'sameDowMean'  => $sameDowMean,
    'initFeature'  => $initFeat,
    'capApplied'   => ($applyCap && $capVal !== null) ? $capVal : null,
    'floorSoft70%' => (int)$floorSoft,
    'targetType'   => $targetType
  ];
}

echo json_encode($response, JSON_UNESCAPED_UNICODE);
