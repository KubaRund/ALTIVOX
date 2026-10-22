<?php
declare(strict_types=1);
require __DIR__ . '/../admin/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
$type=(string)($_GET['type']??'');$limit=max(1,min(50,(int)($_GET['limit']??12)));
$out=[];
foreach(av_records() as $r){
 if(empty($r['published']))continue;
 if($type!==''&&($r['type']??'')!==$type)continue;
 $out[]=[
  'type'=>$r['type'],'title'=>$r['title'],'slug'=>$r['slug'],'status'=>$r['status'],'summary'=>$r['summary'],
  'date'=>$r['date'],'location'=>$r['location'],'metrics'=>$r['metrics'],'tags'=>$r['tags'],'cover'=>$r['cover'],'url'=>av_record_url($r),
  'body'=>$r['body']??'','brand'=>$r['brand']??'','category'=>$r['category']??'','role'=>$r['role']??'',
  'field_use'=>$r['field_use']??'','strengths'=>$r['strengths']??'','limitations'=>$r['limitations']??'','tech_notes'=>$r['tech_notes']??''
 ];
}
usort($out,fn($a,$b)=>strcmp((string)($b['date']?:''),(string)($a['date']?:'')));
echo json_encode(['ok'=>true,'records'=>array_slice($out,0,$limit)],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);