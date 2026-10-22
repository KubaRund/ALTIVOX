<?php
declare(strict_types=1);
require __DIR__ . '/../admin/bootstrap.php';
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');
if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !av_is_installed()) { http_response_code(405); echo '{"ok":false}'; exit; }
$raw=file_get_contents('php://input');$d=json_decode($raw?:'{}',true);if(!is_array($d))$d=[];
$vid=preg_replace('/[^a-zA-Z0-9_-]/','',(string)($d['vid']??''));
if(strlen($vid)<12||strlen($vid)>100){http_response_code(400);echo '{"ok":false}';exit;}
$path=substr((string)($d['path']??'/'),0,500);
if($path===''||$path[0]!=='/')$path='/';
$date=date('Y-m-d');$a=av_read_json('analytics',[]);
$row=$a[$date]??['pageviews'=>0,'visitors'=>[],'pages'=>[],'referrers'=>[],'devices'=>[]];
$row['pageviews']=(int)$row['pageviews']+1;
$row['visitors'][$vid]=1;
$row['pages'][$path]=(int)($row['pages'][$path]??0)+1;
$ref=(string)($d['referrer']??'');
$host='';
if($ref!==''){$u=parse_url($ref);$host=is_array($u)?(string)($u['host']??''):'';}
if($host!=='' && !str_contains($host,'altivox.cz'))$row['referrers'][$host]=(int)($row['referrers'][$host]??0)+1;
$ua=strtolower((string)($_SERVER['HTTP_USER_AGENT']??''));
$device=preg_match('/mobile|android|iphone/',$ua)?'mobile':(preg_match('/tablet|ipad/',$ua)?'tablet':'desktop');
$row['devices'][$device]=(int)($row['devices'][$device]??0)+1;
$a[$date]=$row;
// keep 120 days
ksort($a); while(count($a)>120) array_shift($a);
av_write_json('analytics',$a);
$live=av_read_json('live',[]);$live[$vid]=['ts'=>time(),'path'=>$path];foreach($live as $k=>$x)if((int)($x['ts']??0)<time()-3600)unset($live[$k]);av_write_json('live',$live);
echo '{"ok":true}';