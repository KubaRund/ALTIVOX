<?php
declare(strict_types=1);

function hw2_settings(array $in): array {
    if (isset($in['name']) && !is_string($in['name'])) hw_error('Doplňte název akce.');
    $name = trim($in['name'] ?? 'Halloween');
    if ($name === '' || strlen($name) > 160) hw_error('Doplňte krátký název akce.');
    return ['name'=>$name, 'capacity'=>hw_int($in['capacity'] ?? 30, 1, 30, 'Kapacita'),
        'minutes'=>15, 'gapSeconds'=>hw_int($in['gapSeconds'] ?? 120, 0, 300, 'Rozestup')];
}
function hw2_new(array $in, int $now): array {
    return ['version'=>2, 'id'=>bin2hex(random_bytes(12)),
        'keys'=>array_combine(['admin','cashier','caller','cellar'], array_map(fn()=>bin2hex(random_bytes(32)),range(1,4))),
        'created'=>$now, 'expires'=>$now+30*86400, 'revision'=>0, 'closed'=>false, 'paused'=>false,
        'settings'=>hw2_settings($in), 'nextNumber'=>hw_int($in['start']??1,1,999999,'První skupina'),
        'order'=>0, 'callSeq'=>0, 'groups'=>[], 'calls'=>[], 'requests'=>[]];
}
function hw2_people(array $s, array $statuses): int {
    $sum=0; foreach($s['groups'] as $g) if(in_array($g['status'],$statuses,true)) $sum+=$g['size']; return $sum;
}
function hw2_last_entry(array $s): ?array {
    $last=null; foreach($s['groups'] as $g) if($g['entered']!==null && ($last===null || [$g['entered'],$g['number']]>[$last['entered'],$last['number']])) $last=$g;
    return $last;
}
function hw2_next_allowed(array $s): int {
    $last=hw2_last_entry($s); return $last ? $last['entered']+$s['settings']['gapSeconds'] : 0;
}
function hw2_can_call(array $s, int $now): bool {
    $q=hw_queue($s); return !$s['closed'] && !$s['paused'] && $q && hw2_people($s,['called'])===0 &&
        $now>=hw2_next_allowed($s) && $q[0]['size']+hw2_people($s,['inside'])<=$s['settings']['capacity'];
}
function hw2_apply(array &$s, string $action, array $in, string $role, int $now): array {
    $allowed=['sell'=>['cashier'],'cancel'=>['cashier'],'next'=>['caller'],'repeat'=>['caller'],
        'missed'=>['caller'],'return'=>['caller'],'enter'=>['cellar'],'exit'=>['cellar'],
        'pacing'=>['caller','cellar'],'settings'=>[],'pause'=>['caller'],'close'=>[]];
    if(!isset($allowed[$action])) hw_error('Neznámá akce.');
    if($role!=='admin'&&!in_array($role,$allowed[$action],true)) hw_error('Pro tuto akci nemáte oprávnění.',403);
    if($s['closed']) hw_error('Akce je uzavřená.',409);
    $result=[];
    if($action==='sell') {
        $size=hw_int($in['count']??0,1,6,'Počet lidí');
        if($size>$s['settings']['capacity']) hw_error('Skupina je větší než kapacita.');
        if(count($s['groups'])>=10000||$s['nextNumber']>999999) hw_error('Byl dosažen limit skupin.',429);
        $id=bin2hex(random_bytes(8)); $number=$s['nextNumber']++; $s['order']++;
        $s['groups'][$id]=['id'=>$id,'number'=>$number,'size'=>$size,'order'=>$s['order'],'status'=>'waiting',
            'created'=>$now,'called'=>null,'entered'=>null,'exited'=>null];
        $result=['group'=>$id,'number'=>$number,'size'=>$size];
    } elseif($action==='next') {
        if(!hw2_can_call($s,$now)) hw_error('Další skupinu zatím nelze vyvolat. Ověřte rozestup, vstup a volná místa.',409);
        $g=hw_queue($s)[0]; $s['groups'][$g['id']]['status']='called'; $s['groups'][$g['id']]['called']=$now;
        hw2_record_call($s,$g,$now,false); $result=['group'=>$g['id'],'number'=>$g['number'],'size'=>$g['size']];
    } elseif($action==='repeat') {
        $g=hw_group($s,(string)($in['group']??'')); if($g['status']!=='called') hw_error('Skupina už nečeká u vstupu.',409);
        hw2_record_call($s,$g,$now,true);
    } elseif(in_array($action,['enter','exit','missed','return','cancel'],true)) {
        $id=(string)($in['group']??''); $g=hw_group($s,$id);
        $expected=['enter'=>'called','exit'=>'inside','missed'=>'called','return'=>'missed','cancel'=>'waiting'][$action];
        if($g['status']!==$expected) hw_error('Stav skupiny se změnil. Obnovte přehled.',409);
        if($action==='enter') {
            if($now<hw2_next_allowed($s)) hw_error('Ještě běží rozestup mezi vstupy.',409);
            if(hw2_people($s,['inside'])+$g['size']>$s['settings']['capacity']) hw_error('Sklep je plný.',409);
            $s['groups'][$id]['status']='inside'; $s['groups'][$id]['entered']=$now;
        } elseif($action==='exit') { $s['groups'][$id]['status']='done'; $s['groups'][$id]['exited']=$now; }
        elseif($action==='missed') $s['groups'][$id]['status']='missed';
        elseif($action==='cancel') $s['groups'][$id]['status']='cancelled';
        else { if($g['size']>$s['settings']['capacity']) hw_error('Skupina je větší než kapacita.'); $s['order']++; $s['groups'][$id]['status']='waiting'; $s['groups'][$id]['order']=$s['order']; }
    } elseif($action==='pacing') $s['settings']['gapSeconds']=hw_int($in['gapSeconds']??null,0,300,'Rozestup');
    elseif($action==='settings') {
        $settings=hw2_settings(array_merge($s['settings'],$in)); $max=hw2_people($s,['inside','called']);
        foreach($s['groups'] as $g) if(in_array($g['status'],['waiting','missed'],true)) $max=max($max,$g['size']);
        if($settings['capacity']<$max) hw_error('Kapacita je menší než obsazená místa nebo evidovaná skupina.');
        $s['settings']=$settings;
    } elseif($action==='pause') $s['paused']=!$s['paused'];
    elseif($action==='close') { if(hw2_people($s,['called','inside'])>0) hw_error('Nejdřív vyřešte vstup a odchod skupin.',409); $s['closed']=true; }
    $s['revision']++; return $result;
}
function hw2_record_call(array &$s, array $g, int $now, bool $repeat): void {
    $s['callSeq']++; $s['calls'][]=['seq'=>$s['callSeq'],'time'=>$now,'group'=>$g['id'],'number'=>$g['number'],'size'=>$g['size'],'repeat'=>$repeat];
    $s['calls']=array_slice($s['calls'],-50);
}
function hw2_snapshot(array $s, string $role, int $now): array {
    $duration=900; $gap=$s['settings']['gapSeconds']; $capacity=$s['settings']['capacity']; $releases=[];
    foreach($s['groups'] as $g) if(in_array($g['status'],['called','inside'],true)) {
        $end=$g['status']==='called'?$now+$duration:($g['entered']+$duration>$now?$g['entered']+$duration:PHP_INT_MAX);
        for($i=0;$i<$g['size'];$i++) $releases[]=$end;
    }
    $estimates=[]; $queue=hw_queue($s); $cursor=max($now,hw2_next_allowed($s))+(hw2_people($s,['called'])?$gap:0);
    foreach($queue as $i=>$g) {
        $start=$cursor;
        while($start!==PHP_INT_MAX) {
            $releases=array_values(array_filter($releases,fn($t)=>$t>$start));
            if(count($releases)+$g['size']<=$capacity) break;
            sort($releases); $start=$releases[0];
        }
        $unknown=$start===PHP_INT_MAX; $end=$unknown?PHP_INT_MAX:$start+$duration;
        if(!$unknown) for($j=0;$j<$g['size'];$j++) $releases[]=$end;
        $estimates[$g['id']]=['position'=>$i+1,'eta'=>($s['paused']||$s['closed']||$unknown)?null:$start];
        $cursor=$unknown?PHP_INT_MAX:$start+$gap;
    }
    $counts=array_fill_keys(['waiting','called','inside','done','missed','cancelled'],0); $groups=[]; $sold=0;
    foreach($s['groups'] as $g) { $counts[$g['status']]+=$g['size']; $sold+=$g['size']; $groups[]=$g+($estimates[$g['id']]??['position'=>null,'eta'=>null]); }
    $called=array_values(array_filter($groups,fn($g)=>$g['status']==='called'));
    $out=['version'=>2,'id'=>$s['id'],'role'=>$role,'now'=>$now,'created'=>$s['created'],'expires'=>$s['expires'],
        'revision'=>$s['revision'],'settings'=>$s['settings'],'closed'=>$s['closed'],'paused'=>$s['paused'],
        'nextNumber'=>$s['nextNumber'],'counts'=>$counts,'sold'=>$sold,'soldGroups'=>count($s['groups']),
        'queueGroups'=>count($queue),'called'=>$called,'queue'=>array_slice(array_map(fn($g)=>$g+$estimates[$g['id']],$queue),0,100),
        'calls'=>array_slice($s['calls'],-20),'duration'=>$duration];
    if($role!=='public') {
        $out['groups']=$groups; $out['canCall']=hw2_can_call($s,$now); $out['nextAllowed']=hw2_next_allowed($s);
        $out['lastEntry']=hw2_last_entry($s);
    }
    if($role==='admin') $out['keys']=$s['keys'];
    return $out;
}
