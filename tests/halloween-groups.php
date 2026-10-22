<?php
declare(strict_types=1);
require __DIR__.'/../www/halloween/core.php';
function must(bool $ok,string $why): void { if(!$ok)throw new RuntimeException($why); }
function rejects(callable $action,int $code): void { try{$action();throw new RuntimeException('Expected rejection');}catch(DomainException $e){must($e->getCode()===$code,'Wrong rejection code');} }
$now=1800000000;$s=hw_new(['version'=>2],$now);
must($s['settings']===['name'=>'Halloween','capacity'=>30,'minutes'=>15,'gapSeconds'=>120],'Defaults');
$result=hw_apply($s,'sell',['count'=>4],'cashier',$now);$first=$result['group'];
$second=hw_apply($s,'sell',['count'=>1],'cashier',$now)['group'];
must($s['groups'][$first]['number']===1&&$s['groups'][$second]['number']===2,'One number per group');
rejects(fn()=>hw_apply($s,'sell',['count'=>7],'cashier',$now),400);
rejects(fn()=>hw_apply($s,'sell',['count'=>1],'public',$now),403);
hw_apply($s,'next',[],'caller',$now);rejects(fn()=>hw_apply($s,'next',[],'caller',$now),409);
hw_apply($s,'repeat',['group'=>$first],'caller',$now+20);
must($s['groups'][$first]['called']===$now,'Repeat must not reset waiting timer');
hw_apply($s,'enter',['group'=>$first],'cellar',$now+30);
rejects(fn()=>hw_apply($s,'enter',['group'=>$first],'cellar',$now+31),409);
rejects(fn()=>hw_apply($s,'next',[],'caller',$now+149),409);
hw_apply($s,'next',[],'caller',$now+150);
hw_apply($s,'enter',['group'=>$second],'cellar',$now+151);
$v=hw_snapshot($s,'admin',$now+152);
must($v['sold']===5&&$v['soldGroups']===2&&$v['counts']['inside']===5,'People versus groups');
must($v['lastEntry']['number']===2&&$v['nextAllowed']===$now+271,'Pacing starts at actual entrance');
$public=hw_snapshot($s,'public',$now+152);
foreach(['keys','groups','nextAllowed','lastEntry','canCall'] as $private)must(!isset($public[$private]),'Public privacy: '.$private);
rejects(fn()=>hw_apply($s,'pacing',['gapSeconds'=>0],'cashier',$now),403);
rejects(fn()=>hw_apply($s,'close',[],'admin',$now),409);
hw_apply($s,'exit',['group'=>$first],'cellar',$now+1000);
must(hw2_people($s,['inside'])===1,'Exit releases only its group');

$full=hw_new(['version'=>2,'gapSeconds'=>0],$now);
for($i=0;$i<6;$i++) hw_apply($full,'sell',['count'=>6],'cashier',$now);
for($i=0;$i<5;$i++) { $g=hw_queue($full)[0];hw_apply($full,'next',[],'caller',$now);hw_apply($full,'enter',['group'=>$g['id']],'cellar',$now); }
must(hw2_people($full,['inside'])===30,'Thirty-person occupancy');
rejects(fn()=>hw_apply($full,'next',[],'caller',$now),409);
$estimate=hw_snapshot($full,'caller',$now+300);must($estimate['queue'][0]['eta']===$now+900,'Capacity ETA');
$overdue=hw_snapshot($full,'caller',$now+901);must($overdue['queue'][0]['eta']===null&&$overdue['counts']['inside']===30,'Overdue must never release capacity');
$inside=array_values(array_filter($full['groups'],fn($g)=>$g['status']==='inside'))[0];
hw_apply($full,'exit',['group'=>$inside['id']],'cellar',$now+902);must(hw2_can_call($full,$now+902),'Actual exit enables next');
hw_apply($full,'next',[],'caller',$now+902);
hw_apply($full,'missed',['group'=>hw_queue($full)[0]['id']??array_values(array_filter($full['groups'],fn($g)=>$g['status']==='called'))[0]['id']],'caller',$now+903);

$paced=hw_new(['version'=>2,'gapSeconds'=>120],$now);
for($i=0;$i<3;$i++)hw_apply($paced,'sell',['count'=>1],'cashier',$now);
$v=hw_snapshot($paced,'caller',$now);must(array_column($v['queue'],'eta')===[$now,$now+120,$now+240],'Paced FIFO estimates');
hw_apply($paced,'pause',[],'caller',$now);must(hw_snapshot($paced,'caller',$now)['queue'][0]['eta']===null,'Pause clears ETA');
hw_apply($paced,'close',[],'admin',$now);rejects(fn()=>hw_apply($paced,'sell',['count'=>1],'cashier',$now),409);

$large=hw_new(['version'=>2,'gapSeconds'=>120],$now);
for($i=0;$i<10000;$i++)hw_apply($large,'sell',['count'=>6],'cashier',$now);
$start=microtime(true);$v=hw_snapshot($large,'admin',$now);$elapsed=microtime(true)-$start;
must($v['sold']===60000&&count($v['groups'])===10000,'Long queue must stay complete');
must($elapsed<5,'Long queue calculation unexpectedly slow');
rejects(fn()=>hw_apply($large,'sell',['count'=>1],'cashier',$now),429);
echo 'Group checks passed: numbering, limits, roles, FIFO, pacing, actual entry/exit, privacy, overdue and 10,000 groups in '.round($elapsed,3).'s; peak '.round(memory_get_peak_usage(true)/1048576,1)." MiB.\n";
