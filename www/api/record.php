<?php
declare(strict_types=1);
require __DIR__.'/../admin/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
$type=(string)($_GET['type']??'');$slug=(string)($_GET['slug']??'');
$r=av_find_record($type,$slug);
if(!$r||empty($r['published'])){http_response_code(404);echo json_encode(['ok'=>false]);exit;}
$keys=['id','type','title','slug','status','summary','body','date','location','metrics','tags','cover','photos','brand','category','role','field_use','strengths','limitations','tech_notes','related'];
$out=['ok'=>true,'record'=>[]];
foreach($keys as $k)$out['record'][$k]=$r[$k]??($k==='photos'||$k==='tags'||$k==='related'?[]:'');
echo json_encode($out,JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
