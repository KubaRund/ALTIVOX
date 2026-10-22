<?php
declare(strict_types=1);
require __DIR__.'/../www/halloween/core.php';
function check(bool $value,string $msg):void{if(!$value)throw new RuntimeException($msg);}
function fails(callable $fn,int $code):void{try{$fn();throw new RuntimeException('Expected rejection');}catch(DomainException $e){check($e->getCode()===$code,'Wrong error');}}
$now=1800000000;$s=hw_new(['capacity'=>4,'minutes'=>5,'start'=>10],$now);
$sale=hw_apply($s,'sell',['count'=>3,'together'=>false],'cashier',$now);check($sale['numbers']===[10,11,12],'Sequential ticket numbers');
hw_apply($s,'sell',['count'=>1,'together'=>true],'cashier',$now+1);
hw_apply($s,'merge',['numbers'=>[10,13]],'cashier',$now+2);
$q=hw_queue($s);check(array_column($q,'numbers')===[[11],[12],[10,13]],'Merged group must use latest purchase order');
hw_apply($s,'next',[],'caller',$now+3);hw_apply($s,'next',[],'caller',$now+3);hw_apply($s,'next',[],'caller',$now+3);
check(hw_people($s,['called','inside'])===4,'All capacity is reserved');
hw_apply($s,'sell',['count'=>1],'cashier',$now+4);fails(fn()=>hw_apply($s,'next',[],'caller',$now+4),409);
$called=array_values(array_filter($s['groups'],fn($g)=>$g['status']==='called'))[0];
hw_apply($s,'enter',['group'=>$called['id']],'cellar',$now+5);fails(fn()=>hw_apply($s,'enter',['group'=>$called['id']],'cellar',$now+6),409);
$revision=$s['callSeq'];$remaining=array_values(array_filter($s['groups'],fn($g)=>$g['status']==='called'))[0];
hw_apply($s,'repeat',['group'=>$remaining['id']],'caller',$now+7);check($s['callSeq']===$revision+1&&hw_people($s,['called','inside'])===4,'Repeat must not reserve twice');
hw_apply($s,'exit',['group'=>$called['id']],'cellar',$now+305);check(hw_people($s,['inside'])===0,'Exit releases actual occupancy');
hw_apply($s,'next',[],'caller',$now+306);fails(fn()=>hw_apply($s,'sell',['count'=>1],'public',$now+307),403);
fails(fn()=>hw_apply($s,'settings',['capacity'=>1],'cashier',$now+307),403);
fails(fn()=>hw_apply($s,'merge',['numbers'=>[11,12]],'cashier',$now+307),409);
$v=hw_snapshot($s,'public',$now+307,10);check(!isset($v['keys'])&&!isset($v['groups'])&&!isset($v['salesBuckets']),'Public snapshot must exclude staff credentials and private collections');
check($v['durationSamples']===1&&$v['duration']===300,'Observed service time updates estimate');
check($v['ticket']['numbers']===[10,13],'Visitor sees complete merged group');
$s2=hw_new(['capacity'=>3,'minutes'=>5],$now);hw_apply($s2,'sell',['count'=>3],'cashier',$now);$g=hw_queue($s2)[0];
hw_apply($s2,'next',[],'caller',$now);hw_apply($s2,'missed',['group'=>$g['id']],'caller',$now+10);hw_apply($s2,'settings',['capacity'=>1,'minutes'=>5],'admin',$now+11);
fails(fn()=>hw_apply($s2,'return',['group'=>$g['id']],'caller',$now+12),400);
$s3=hw_new(['capacity'=>2,'minutes'=>5],$now);hw_apply($s3,'sell',['count'=>3,'together'=>false],'cashier',$now);
$v=hw_snapshot($s3,'public',$now);check($v['queue'][0]['eta']===$now&&$v['queue'][1]['eta']===$now&&$v['queue'][2]['eta']===$now+300,'Capacity-based ETA simulation');
hw_apply($s3,'pause',[],'caller',$now);$v=hw_snapshot($s3,'public',$now);check($v['finish']===null&&$v['queue'][0]['eta']===null,'Pause suppresses estimates');
fails(fn()=>hw_apply($s3,'next',[],'caller',$now),409);
hw_apply($s3,'close',[],'admin',$now);fails(fn()=>hw_apply($s3,'sell',['count'=>1],'cashier',$now),409);
echo "Core checks passed: numbering, merge fairness, reservation, capacity, transitions, repeat, permissions, privacy, measured ETA, missed return, pause and close.\n";
