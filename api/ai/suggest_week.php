<?php
// C:\xampp\htdocs\cafeteria_api\ai\suggest_week.php
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
function isYmd($s): bool { return (bool)preg_match('/^\d{4}-\d{2}-\d{2}$/', (string)$s); }
function phpDow(string $date): int { $dt=new DateTime($date); return (int)$dt->format('N'); } // 1..7 Mon..Sun
function monthNum(string $date): int { $dt=new DateTime($date); return (int)$dt->format('n'); }
function dowFa(int $phpDow): string {
  $map=[1=>'دوشنبه',2=>'سه‌شنبه',3=>'چهارشنبه',4=>'پنجشنبه',5=>'جمعه',6=>'شنبه',7=>'یکشنبه'];
  return $map[$phpDow] ?? (string)$phpDow;
}
function mealTxt($meal): string {
  if (is_numeric($meal)) { $m=(int)$meal; return $m===0?'breakfast':($m===1?'lunch':'dinner'); }
  $m=strtolower(trim((string)$meal));
  return in_array($m,['breakfast','lunch','dinner'],true)?$m:'lunch';
}
function mealId($meal): int { $t=mealTxt($meal); return $t==='breakfast'?0:($t==='lunch'?1:2); }
function clamp(float $x,float $lo,float $hi): float { return max($lo,min($hi,$x)); }
function addDays(string $ymd,int $d): string { $dt=new DateTime($ymd); $dt->modify(($d>=0?'+':'').$d.' day'); return $dt->format('Y-m-d'); }

/* ---------- read params ---------- */
$raw=file_get_contents('php://input'); $payload=[];
if($raw){$j=json_decode($raw,true); if(json_last_error()===JSON_ERROR_NONE&&is_array($j))$payload=$j;}
if(!$payload){ if(!empty($_POST))$payload=$_POST; elseif(!empty($_REQUEST))$payload=$_REQUEST; }

$startDate   = $payload['start_date'] ?? date('Y-m-d');
$days        = isset($payload['days'])?(int)$payload['days']:7;
$selfId      = isset($payload['self_id'])?(int)$payload['self_id']:null;
$mealsIn     = $payload['meals'] ?? ['breakfast','lunch','dinner'];
$kPerMeal    = isset($payload['per_meal_count'])?max(1,(int)$payload['per_meal_count']):1;
$applyCap    = isset($payload['apply_cap'])?(int)$payload['apply_cap']:1;
$noRepeatGap = isset($payload['no_repeat_gap'])?max(0,(int)$payload['no_repeat_gap']):2;
$includeExplain = isset($payload['explain'])?(int)$payload['explain']:1;
$includeSignals = isset($payload['signals'])?(int)$payload['signals']:0;

if(!$selfId) json_error(400,'self_id الزامی است');
if(!isYmd($startDate)) json_error(400,'start_date نامعتبر (yyyy-mm-dd)');
if(!is_array($mealsIn)||count($mealsIn)===0) $mealsIn=['breakfast','lunch','dinner'];
$meals=array_values(array_map('mealTxt',$mealsIn));
$days=min(max($days,1),14);

/* ---------- load model ---------- */
$modelPath=__DIR__.'/../models/food_predictor.model';
$metaPath=__DIR__.'/../models/model_meta.json';
$scalerPath=__DIR__.'/../models/scaler.json';
if(!file_exists($modelPath)) json_error(500,'Model not found. ابتدا ai/train_ml.php را اجرا کنید.');

$manager=new ModelManager();
try{$model=$manager->restoreFromFile($modelPath);}catch(Throwable $e){json_error(500,'Failed to load model',['detail'=>$e->getMessage()]);}

$meta=file_exists($metaPath)?(json_decode((string)file_get_contents($metaPath),true)?:[]):[];
$targetType=$meta['target']??'rate';
$rateClipMax=isset($meta['rate_clip_max'])?(float)$meta['rate_clip_max']:1.5;
$ignoreMealMd=(int)($meta['ignore_meal']??0);

$mins=$maxs=[];
if(file_exists($scalerPath)){
  $sc=json_decode((string)file_get_contents($scalerPath),true);
  if(isset($sc['min'])&&isset($sc['max'])){$mins=$sc['min'];$maxs=$sc['max'];}
}
$scale=function(array $f)use($mins,$maxs){
  if(!$mins||!$maxs) return $f;
  $o=$f;$n=count($f);
  for($j=0;$j<$n;$j++){ $min=(float)($mins[$j]??0.0); $max=(float)($maxs[$j]??1.0); $r=($max-$min)?:1.0; $o[$j]=($f[$j]-$min)/$r; }
  return $o;
};

/* ---------- history (FIX: فقط نام‌دار) ---------- */
$statusList="('reserved','paid','used','completed')";

/* می‌سازیم: IN (:m0,:m1,...) و پارامترهای نام‌دار */
$mealPhs=[]; $mealParams=[];
foreach($meals as $idx=>$m){ $mealPhs[]=':m'.$idx; $mealParams[':m'.$idx]=$m; }

$sqlHist="
  SELECT d.date, d.meal_type, d.self_id, d.food_id, d.initial_count,
         COALESCE(cnt.cnt,0) AS demand
  FROM daily_food_plans d
  LEFT JOIN (
    SELECT r.daily_food_plan_id, COUNT(*) AS cnt
    FROM reservations r
    WHERE r.status IN $statusList
    GROUP BY r.daily_food_plan_id
  ) cnt ON cnt.daily_food_plan_id = d.id
  WHERE d.self_id = :self
    AND d.meal_type IN (".implode(',', $mealPhs).")
  ORDER BY d.date ASC
";
try{
  $histStmt=$pdo->prepare($sqlHist);
  $execParams = array_merge([':self'=>$selfId], $mealParams);
  $histStmt->execute($execParams);
  $rows=$histStmt->fetchAll(PDO::FETCH_ASSOC);
}catch(Throwable $e){
  json_error(500,'DB error (history)', ['detail'=>$e->getMessage(),'sql'=>$sqlHist]);
}

/* ---------- foods ---------- */
try{
  $foodsQ=$pdo->query("SELECT id,name,is_available FROM foods");
$foodsAll=[];
foreach($foodsQ->fetchAll(PDO::FETCH_ASSOC) as $f){
  $foodsAll[(int)$f['id']] = [
    'name' => $f['name'],
    'is_active' => (int)$f['is_available']  // اینجا مپ می‌کنیم به همون کلید قبلی
  ];
}
}catch(Throwable $e){
  json_error(500,'DB error (foods)', ['detail'=>$e->getMessage()]);
}

/* ---------- per-food stats ---------- */
$perFood=[];
foreach($rows as $r){
  $food=(int)$r['food_id']; $date=(string)$r['date'];
  $init=(int)$r['initial_count']; $dem=(int)$r['demand']; $dow=phpDow($date);
  if(!isset($perFood[$food])){
    $perFood[$food]=[
      'name'=>$foodsAll[$food]['name']??('food#'.$food),
      'active'=>$foodsAll[$food]['is_active']??1,
      'rates'=>[],
      'rates_by_dow'=>[1=>[],2=>[],3=>[],4=>[],5=>[],6=>[],7=>[]],
      'inits'=>[],
      'last_rate'=>null,
      'last_date'=>null
    ];
  }
  if($init>0){
    $rate=$dem/$init;
    $perFood[$food]['rates'][]=$rate;
    $perFood[$food]['rates_by_dow'][$dow][]=$rate;
    $perFood[$food]['inits'][]=$init;
  }
  if($perFood[$food]['last_date']===null || $date>$perFood[$food]['last_date']){
    $perFood[$food]['last_date']=$date;
    $perFood[$food]['last_rate']=($init>0)?($dem/$init):null;
  }
}
$scoreForDow=function(int $foodId,int $targetDow)use($perFood):float{
  $st=$perFood[$foodId]??null; if(!$st) return 0.0;
  $avg=count($st['rates'])?array_sum($st['rates'])/count($st['rates']):0.0;
  $same=$st['rates_by_dow'][$targetDow]??[]; $sameAvg=$same?(array_sum($same)/count($same)):$avg;
  $last=$st['last_rate']??$avg;
  return 0.6*$avg + 0.25*$sameAvg + 0.15*($last??$avg);
};
$typicalInit=function(int $foodId)use($perFood):int{
  $st=$perFood[$foodId]??null; if(!$st||!$st['inits']) return 0;
  return (int)round(array_sum($st['inits'])/count($st['inits']));
};

/* ---------- helpers for a target day ---------- */
$historySignals=function(string $targetDate,string $mealText,int $selfId,int $foodId)use($pdo,$statusList){
  try{
    $st=$pdo->prepare("
      SELECT d.date AS ddate, d.initial_count,
             COALESCE(cnt.cnt,0) AS demand
      FROM daily_food_plans d
      LEFT JOIN (
        SELECT r.daily_food_plan_id, COUNT(*) AS cnt
        FROM reservations r
        WHERE r.status IN $statusList
        GROUP BY r.daily_food_plan_id
      ) cnt ON cnt.daily_food_plan_id = d.id
      WHERE d.date < :date AND d.meal_type=:meal AND d.self_id=:self AND d.food_id=:food
      ORDER BY d.date ASC
    ");
    $st->execute([':date'=>$targetDate,':meal'=>$mealText,':self'=>$selfId,':food'=>$foodId]);
    $hist=$st->fetchAll(PDO::FETCH_ASSOC)?:[];
  }catch(Throwable $e){
    json_error(500,'DB error (signals)', ['detail'=>$e->getMessage()]);
  }

  $lastDemand=0.0;$mean7=0.0;$sameDowMean=0.0;
  if($hist){
    $dems=[];$same=[]; $targetDow=phpDow($targetDate);
    foreach($hist as $h){ $dems[]=(int)$h['demand']; if(phpDow($h['ddate'])===$targetDow)$same[]=(int)$h['demand']; }
    $lastDemand=(float)end($dems);
    $tail=array_slice($dems,-7); if($tail)$mean7=array_sum($tail)/count($tail);
    if($same)$sameDowMean=array_sum($same)/count($same);
  }
  return [$lastDemand,$mean7,$sameDowMean];
};
$initForTarget=function(string $date,string $mealText,int $selfId,int $foodId)use($pdo){
  try{
    $q=$pdo->prepare("SELECT initial_count FROM daily_food_plans WHERE date=:d AND meal_type=:m AND self_id=:s AND food_id=:f ORDER BY id DESC LIMIT 1");
    $q->execute([':d'=>$date,':m'=>$mealText,':s'=>$selfId,':f'=>$foodId]);
    $r=$q->fetch(PDO::FETCH_ASSOC);
    return ($r&&$r['initial_count']!==null)?(int)$r['initial_count']:null;
  }catch(Throwable $e){
    json_error(500,'DB error (init target)', ['detail'=>$e->getMessage()]);
  }
};

/* ---------- selection loop ---------- */
$week=[]; $usedRecently=[]; $choicesByDay=[];
for($i=0;$i<$days;$i++){
  $date=addDays($startDate,$i); $dow=phpDow($date);
  $dayNode=['date'=>$date,'weekday'=>dowFa($dow),'meals'=>[]];

  foreach($meals as $mealText){
    $meal_i=$ignoreMealMd===1?99:mealId($mealText);

    // candidates: فعال + سابقه
    $candidateIds=[];
    foreach($perFood as $fid=>$st){ if(($st['active']??1)!=1) continue; if(empty($st['rates'])) continue; $candidateIds[]=(int)$fid; }
    if(!$candidateIds){ foreach($foodsAll as $fid=>$fi) if(($fi['is_active']??1)==1) $candidateIds[]=(int)$fid; }

    // scoring
    $scored=[];
    foreach($candidateIds as $fid){
      $score=$scoreForDow($fid,$dow);
      if(isset($usedRecently[$fid])){ $gap=$i-$usedRecently[$fid]; if($gap>=0&&$gap<$noRepeatGap){ $score*=0.6; } }
      $scored[]=['food_id'=>$fid,'score'=>$score];
    }
    usort($scored,function($a,$b){ return $b['score'] <=> $a['score']; });

    // pick top-k (بدون تکرار روز)
    $picked=[]; $choicesByDay[$i]=$choicesByDay[$i]??[];
    foreach($scored as $row){
      if(count($picked)>=$kPerMeal)break;
      $fid=(int)$row['food_id'];
      if(isset($choicesByDay[$i][$fid]))continue;
      $picked[]=$fid; $choicesByDay[$i][$fid]=true; $usedRecently[$fid]=$i;
    }

    // predict capacity
    $items=[];
    foreach($picked as $fid){
      $foodName=$foodsAll[$fid]['name']??('food#'.$fid);
      [$lastDemand,$mean7,$sameDowMean]=$historySignals($date,$mealText,$selfId,$fid);
      $capVal=$initForTarget($date,$mealText,$selfId,$fid);
      $initFeat=$capVal ?? $typicalInit($fid);

      $raw=[(int)$dow,(int)monthNum($date),(int)$meal_i,(int)$selfId,(int)$fid,(int)$initFeat,(float)$lastDemand,(float)$mean7,(float)$sameDowMean];
      $features=$scale($raw);

      $predRaw=(float)$model->predict($features);
      if($targetType==='rate'){
        $rate=clamp($predRaw,0.0,$rateClipMax);
        $ref=$capVal!==null&&$capVal>0?(float)$capVal:(float)max(1,$initFeat);
        $yhat=$rate*$ref;
      } else { $yhat=max(0.0,$predRaw); }

      $floorSoft=0.0;
      if($mean7>0&&$sameDowMean>0) $floorSoft=0.5*$mean7+0.5*$sameDowMean;
      elseif($mean7>0) $floorSoft=$mean7; elseif($sameDowMean>0) $floorSoft=$sameDowMean;
      $floorSoft=max(1.0,floor($floorSoft*0.7));
      $yhat=max($yhat,$floorSoft);
      if($applyCap && $capVal!==null && $capVal>0) $yhat=min($yhat,(float)$capVal);
      $yhat=(int)max(0,round($yhat));

      $it=['food_id'=>$fid,'food_name'=>$foodName,'suggested_capacity'=>$yhat];
      if($includeExplain){
        $parts=[];
        if($mean7>0) $parts[]='میانگین ۷ حضور اخیر ≈ '.(int)round($mean7);
        if($sameDowMean>0) $parts[]='میانگین همین روز هفته ≈ '.(int)round($sameDowMean);
        if($lastDemand>0) $parts[]='آخرین دفعه = '.(int)$lastDemand;
        if($initFeat>0) $parts[]='ظرفیت مرجع = '.(int)$initFeat;
        if($applyCap && $capVal!==null) $parts[]='سقف اعمال شد: '.(int)$capVal;
        $it['explain']='با توجه به '.implode('، ',$parts).' → '.$yhat;
      }
      if($includeSignals){ $it['signals']=['lastDemand'=>$lastDemand,'mean7'=>$mean7,'sameDowMean'=>$sameDowMean,'initFeature'=>$initFeat,'capApplied'=>($applyCap&&$capVal!==null)?$capVal:null,'floorSoft70%'=> (int)$floorSoft]; }
      $items[]=$it;
    }

    $dayNode['meals'][]=['meal'=>$mealText,'items'=>$items];
  }

  $week[]=$dayNode;
}

/* ---------- summary ---------- */
$total=0;$byMeal=[];
foreach($week as $d){
  foreach($d['meals'] as $m){
    $s=0; foreach($m['items'] as $it){$s+=(int)$it['suggested_capacity'];}
    $total+=$s;
    $byMeal[$m['meal']]=($byMeal[$m['meal']]??0)+$s;
  }
}

/* ---------- response ---------- */
echo json_encode([
  'status'=>'ok',
  'input'=>[
    'start_date'=>$startDate,'days'=>$days,'self_id'=>$selfId,
    'meals'=>$meals,'per_meal_count'=>$kPerMeal,'apply_cap'=>$applyCap,'no_repeat_gap'=>$noRepeatGap
  ],
  'summary'=>['total_portions'=>$total,'totals_by_meal'=>$byMeal],
  'week'=>$week
], JSON_UNESCAPED_UNICODE);
