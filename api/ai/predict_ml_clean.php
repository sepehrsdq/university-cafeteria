<?php
// C:\xampp\htdocs\cafeteria_api\ai\predict_ml_clean.php
declare(strict_types=1);
ini_set('display_errors', '1');
error_reporting(E_ALL);

require __DIR__ . '/../vendor/autoload.php';
require __DIR__ . '/../bootstrap.php';
require __DIR__ . '/../db.php';

use Phpml\ModelManager;

/* helpers */
function json_error(int $code, string $msg, array $extra = []) { http_response_code($code); echo json_encode(array_merge(['status'=>'error','message'=>$msg], $extra)); exit; }
function validate_date(string $date): bool { $dt=DateTime::createFromFormat('Y-m-d',$date); return $dt && $dt->format('Y-m-d')===$date; }
function mealTxt($meal): string { if (is_numeric($meal)) { $m=(int)$meal; return $m===0?'breakfast':($m===1?'lunch':'dinner'); } $m=strtolower(trim((string)$meal)); return in_array($m,['breakfast','lunch','dinner'],true)?$m:'lunch'; }
function mealId($meal): int { $t=mealTxt($meal); return $t==='breakfast'?0:($t==='lunch'?1:2); }
function phpDow(string $date): int { $dt=DateTime::createFromFormat('Y-m-d',$date); return $dt?(int)$dt->format('N'):1; }
function monthNum(string $date): int { $dt=DateTime::createFromFormat('Y-m-d',$date); return $dt?(int)$dt->format('n'):1; }
function dowFa(int $phpDow): string { $map=[1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنجشنبه',5=>'جمعه',6=>'شنبه',7=>'یکشنبه']; return $map[$phpDow] ?? (string)$phpDow; }

/* enforce POST */
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { json_error(405,'Method Not Allowed. Use POST.'); }

/* read body */
$raw = file_get_contents('php://input'); $payload = [];
if ($raw) { $j=json_decode($raw,true); if (json_last_error()===JSON_ERROR_NONE && is_array($j)) $payload=$j; }
if (!$payload) { if (!empty($_POST)) $payload=$_POST; elseif (!empty($_REQUEST)) $payload=$_REQUEST; }

/* params */
$date     = $payload['date']     ?? null;
$meal     = $payload['meal']     ?? null;
$selfId   = isset($payload['self_id']) ? (int)$payload['self_id'] : null;
$foodId   = isset($payload['food_id']) ? (int)$payload['food_id'] : null;
$foodName = $payload['food_name'] ?? null;
$applyCap = isset($payload['apply_cap']) ? (int)$payload['apply_cap'] : 1;
$explain  = isset($payload['explain'])  ? (int)$payload['explain']  : 0;
$signalsQ = isset($payload['signals'])  ? (int)$payload['signals']  : 0;

$historyCutoff = $payload['history_cutoff'] ?? null; // اختیاری: برای بک‌تست
$upliftPct     = isset($payload['uplift_pct']) ? (float)$payload['uplift_pct'] : 10.0; // ⬅️ پیش‌فرض ۱۰٪
$ignoreMealFallback = isset($payload['ignore_meal_fallback']) ? (int)$payload['ignore_meal_fallback'] : 1; // ⬅️

if (!$date || !$meal || $selfId===null || ($foodId===null && !$foodName)) {
  json_error(400,'params required: date, meal, self_id, and one of (food_id | food_name)', ['got_keys'=>array_keys($payload)]);
}
if (!validate_date($date)) json_error(400,'Invalid date format. Use YYYY-MM-DD', ['value'=>$date]);
if ($historyCutoff && !validate_date($historyCutoff)) json_error(400,'Invalid history_cutoff date. Use YYYY-MM-DD', ['value'=>$historyCutoff]);

$meal_text = mealTxt($meal);
$meal_id   = mealId($meal);
$dow       = phpDow($date);
$month     = monthNum($date);
$histLimit = $historyCutoff ?: $date;

/* resolve food_id */
if ($foodId === null) {
  $q=$pdo->prepare("SELECT id FROM foods WHERE name=:n LIMIT 1");
  $q->execute([':n'=>$foodName]);
  $row=$q->fetch(PDO::FETCH_ASSOC);
  if(!$row) json_error(404,'food_name not found');
  $foodId=(int)$row['id'];
}

/* load model + scaler + meta */
$modelPath=__DIR__.'/../models/food_predictor.model';
$scalerPath=__DIR__.'/../models/scaler.json';
$metaPath=__DIR__.'/../models/model_meta.json';
if (!file_exists($modelPath)) json_error(500,'Model not found. Train via ai/train_ml.php first.');
if (!file_exists($scalerPath)) json_error(500,'Scaler not found. Retrain model to create scaler.json.');
$meta=['ignore_meal'=>0];
if (file_exists($metaPath)) { $tmp=json_decode(file_get_contents($metaPath), true); if (is_array($tmp)) $meta=array_merge($meta,$tmp); }

try {
  $manager=new ModelManager(); /** @var \Phpml\Estimator $model */
  $model=$manager->restoreFromFile($modelPath);
  $scaler=json_decode(file_get_contents($scalerPath), true);
  if (!is_array($scaler) || !isset($scaler['min'],$scaler['max'])) throw new RuntimeException('Bad scaler.json');
} catch(Throwable $e){ json_error(500,'Failed to load model/scaler',['detail'=>$e->getMessage()]); }

/* build features (past-only) + robust fallbacks */
try {
  // تاریخچه همان وعده (strict)
  $histSql = "
    SELECT d.date AS ddate, d.meal_type, COUNT(r.id) AS demand
    FROM daily_food_plans d
    LEFT JOIN reservations r ON r.daily_food_plan_id=d.id AND r.status='reserved'
    WHERE d.self_id=:self AND d.food_id=:food AND d.date<:date AND d.date<=:histlimit
      AND d.meal_type=:meal
    GROUP BY d.date, d.meal_type
    ORDER BY d.date ASC
  ";
  $st=$pdo->prepare($histSql);
  $st->execute([':self'=>$selfId, ':food'=>$foodId, ':date'=>$date, ':histlimit'=>$histLimit, ':meal'=>$meal_text]);
  $hist=$st->fetchAll(PDO::FETCH_ASSOC) ?: [];

  // تاریخچه cross-meal برای fallback (وقتی ignore_meal آموزش فعال بوده یا کاربر خواسته)
  $histAny = [];
  if ($meta['ignore_meal'] || $ignoreMealFallback) {
    $st2=$pdo->prepare("
      SELECT d.date AS ddate, COUNT(r.id) AS demand
      FROM daily_food_plans d
      LEFT JOIN reservations r ON r.daily_food_plan_id=d.id AND r.status='reserved'
      WHERE d.self_id=:self AND d.food_id=:food
        AND d.date<:date AND d.date<=:histlimit
        AND d.date>=DATE_SUB(:histlimit, INTERVAL 30 DAY) -- ⬅️ پنجره ۳۰ روزه اخیر
      GROUP BY d.date
      ORDER BY d.date ASC
    ");
    $st2->execute([':self'=>$selfId, ':food'=>$foodId, ':date'=>$date, ':histlimit'=>$histLimit]);
    $histAny=$st2->fetchAll(PDO::FETCH_ASSOC) ?: [];
  }

  /* signals */
  $lastDemand=0.0; $mean7=0.0; $sameDowMean=0.0;
  if ($hist) {
    $demands=[]; $same=[];
    foreach($hist as $h){
      $d=(int)$h['demand']; $demands[]=$d;
      if (phpDow($h['ddate'])===$dow) $same[]=$d;
    }
    $lastDemand=(float)end($demands);
    $tail=array_slice($demands,-7);
    if ($tail) $mean7=array_sum($tail)/count($tail);
    if ($same) $sameDowMean=array_sum($same)/count($same);
  }

  // fallback ماهانه: اگر mean7/sameDowMean کم بودند
  $mean30_any=0.0; $usedFallback=false;
  if ($histAny) {
    $vals=array_map(fn($r)=>(int)$r['demand'],$histAny);
    if ($vals) $mean30_any=array_sum($vals)/count($vals);
  }
  if (($mean7==0.0 && $sameDowMean==0.0 && $lastDemand==0.0) && $mean30_any>0.0) {
    // هیچ سیگنال سختی نبود، از ۳۰ روزه استفاده کن
    $usedFallback=true;
    $mean7 = $mean30_any; // برای اینکه کف/توضیح خالی نماند
  }

  // initCount/cap
  $capVal=null; $initCount=0;
  $sc=$pdo->prepare("SELECT initial_count FROM daily_food_plans WHERE date=:date AND meal_type=:meal AND self_id=:self AND food_id=:food ORDER BY id DESC LIMIT 1");
  $sc->execute([':date'=>$date, ':meal'=>$meal_text, ':self'=>$selfId, ':food'=>$foodId]);
  if ($rc=$sc->fetch(PDO::FETCH_ASSOC)) { if ($rc['initial_count']!==null){ $initCount=(int)$rc['initial_count']; $capVal=$initCount; } }
  if ($initCount===0){
    $stt=$pdo->prepare("
      SELECT ROUND(AVG(initial_count)) AS typical_init
      FROM daily_food_plans
      WHERE self_id=:self AND food_id=:food AND initial_count>0
        AND date<=:histlimit
    ");
    $stt->execute([':self'=>$selfId, ':food'=>$foodId, ':histlimit'=>$histLimit]);
    if ($rt=$stt->fetch(PDO::FETCH_ASSOC)) if ($rt['typical_init']!==null) $initCount=(int)$rt['typical_init'];
  }

} catch(Throwable $e){ json_error(500,'DB error while building features',['detail'=>$e->getMessage()]); }

/* features raw (respect training ignore_meal) */
$meal_id_for_model = $meal_id;
if (!empty($meta['ignore_meal'])) $meal_id_for_model = 99;

$featuresRaw = [
  (int)$dow,
  (int)$month,
  (int)$meal_id_for_model,
  (int)$selfId,
  (int)$foodId,
  (int)$initCount,
  (float)$lastDemand,
  (float)$mean7,
  (float)$sameDowMean
];

/* apply Min–Max scaling */
$mins=$scaler['min']; $maxs=$scaler['max']; $dim=9; $features=$featuresRaw;
for($j=0;$j<$dim;$j++){
  $range = ($maxs[$j]-$mins[$j]); if ($range == 0.0) $range = 1.0;
  $features[$j] = ($featuresRaw[$j]-$mins[$j]) / $range;
}

/* predict */
try {
  $pred=$model->predict($features);
  $yhat=(int)max(0, round((float)$pred));
} catch(Throwable $e){ json_error(500,'Prediction failed',['detail'=>$e->getMessage()]); }

/* کف قوی + blend تاریخچه */
$y_hist=0; $w=0.0;
if ($mean7>0){ $y_hist+=0.50*$mean7; $w+=0.50; }
if ($sameDowMean>0){ $y_hist+=0.30*$sameDowMean; $w+=0.30; }
if ($lastDemand>0){ $y_hist+=0.20*$lastDemand; $w+=0.20; }
$y_hist=$w>0?(int)round($y_hist/$w):0;

$floorSoft=0;
if ($y_hist>0) $floorSoft=(int)max(1, round($y_hist*0.9));
elseif ($mean30_any>0) $floorSoft=(int)max(1, round($mean30_any*0.9));
elseif ($sameDowMean>0 || $mean7>0) $floorSoft=(int)max(1, round(max($mean7,$sameDowMean)*0.9));
elseif ($lastDemand>0) $floorSoft=(int)max(1, round($lastDemand*0.9));

if ($initCount>0){ $minByInit=(int)round($initCount*0.4); $floorSoft=max($floorSoft,$minByInit); }

$yhat=max($yhat,$floorSoft);

/* ⬅️ uplift (مثلاً +۱۰٪) */
if ($upliftPct > 0) {
  $yhat = (int)max(0, round($yhat * (1.0 + $upliftPct/100.0)));
}

/* سقف */
if ($applyCap && isset($capVal) && $capVal>0) $yhat=min($yhat,$capVal);

/* human explain */
$parts=[];
if ($mean7>0)         $parts[]="میانگین ۷ وقوع اخیر ≈ ".(int)round($mean7);
if ($sameDowMean>0)   $parts[]="میانگین همان روز هفته ≈ ".(int)round($sameDowMean);
if ($lastDemand>0)    $parts[]="آخرین‌بار = ".(int)$lastDemand;
if ($mean30_any>0)    $parts[]="میانگین ۳۰ روز اخیر (همه وعده‌ها) ≈ ".(int)round($mean30_any);
if ($upliftPct>0)     $parts[]="افزایش احتیاطی +".(int)$upliftPct."٪";
if ($applyCap && isset($capVal)) $parts[]="سقف ".(int)$capVal." اعمال شد";
$explainText = "با توجه به ".(count($parts)?implode('، ',$parts):'الگوهای تاریخی')." → پیشنهاد: {$yhat}";

/* response */
$res=[
  'status'=>'ok',
  'result'=>['suggested_capacity'=>$yhat],
  'input'=>['date'=>$date,'weekday'=>dowFa($dow),'meal'=>$meal_text,'self_id'=>$selfId,'food_id'=>$foodId]
];
if ($explain) $res['explain']=$explainText;
if ($signalsQ){
  $res['signals']=[
    'lastDemand'=>$lastDemand,
    'mean7'=>$mean7,
    'sameDowMean'=>$sameDowMean,
    'mean30_anymeal'=>$mean30_any,
    'initCount'=>$initCount,
    'capApplied'=>($applyCap && isset($capVal))?$capVal:null,
    'histBlend90%'=>$y_hist,
    'floorSoft'=>$floorSoft,
    'uplift_pct'=>$upliftPct,
    'usedFallback30d'=>$usedFallback?1:0,
    'model_ignore_meal'=>(int)!empty($meta['ignore_meal'])
  ];
}
echo json_encode($res);
