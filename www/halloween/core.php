<?php
declare(strict_types=1);
// Queue rules are shared by the API and deterministic tests.
function hw_error(string $message, int $code = 400): never { throw new DomainException($message, $code); }
function hw_int(mixed $v, int $min, int $max, string $label): int {
    if (filter_var($v, FILTER_VALIDATE_INT) === false || (int)$v < $min || (int)$v > $max) hw_error("$label: zadejte celé číslo $min–$max.");
    return (int)$v;
}
function hw_settings(array $in): array {
    if(isset($in['name'])&&!is_string($in['name']))hw_error('Název musí být text.');
    $name = trim($in['name'] ?? 'Školní Halloween');
    if ($name === '' || strlen($name) > 160) hw_error('Doplňte krátký název akce.');
    $minutes = filter_var($in['minutes'] ?? 5, FILTER_VALIDATE_FLOAT);
    if ($minutes === false || $minutes < 0.5 || $minutes > 60) hw_error('Délka průchodu musí být 0,5–60 minut.');
    return ['name' => $name, 'capacity' => hw_int($in['capacity'] ?? 6, 1, 100, 'Kapacita'), 'minutes' => $minutes];
}
function hw_new(array $in, int $now): array {
    return ['id' => bin2hex(random_bytes(12)), 'keys' => array_combine(['admin','cashier','caller','cellar'], array_map(fn() => bin2hex(random_bytes(32)), range(1,4))),
        'created' => $now, 'expires' => $now + 30 * 86400, 'revision' => 0, 'closed' => false, 'paused' => false,
        'settings' => hw_settings($in), 'nextNumber' => hw_int($in['start'] ?? 1, 1, 999999, 'První vstupenka'),
        'order' => 0, 'callSeq' => 0, 'groups' => [], 'calls' => [], 'sales' => [], 'requests' => []];
}
function hw_role(array $state, string $key): string {
    if ($key !== '') foreach ($state['keys'] as $role => $secret) if (hash_equals($secret, $key)) return $role;
    return 'public';
}
function hw_people(array $s, array $statuses): int {
    return array_sum(array_map(fn($g) => in_array($g['status'], $statuses, true) ? count($g['numbers']) : 0, array_values($s['groups'])));
}
function hw_queue(array $s): array {
    $groups = array_values(array_filter($s['groups'], fn($g) => $g['status'] === 'waiting'));
    usort($groups, fn($a,$b) => ($a['order'] <=> $b['order']) ?: strcmp($a['id'],$b['id'])); return $groups;
}
function hw_group(array $s, string $id): array {
    if (!isset($s['groups'][$id])) hw_error('Skupina už neexistuje. Obnovte přehled.', 409);
    return $s['groups'][$id];
}
function hw_call(array &$s, string $id, int $now, bool $repeat = false): void {
    $g = hw_group($s, $id);
    if ($g['status'] !== ($repeat ? 'called' : 'waiting')) hw_error('Tuto skupinu nyní nelze vyvolat.', 409);
    if (!$repeat) {
        if ($s['paused']) hw_error('Vyvolávání je pozastavené.', 409);
        if (count($g['numbers']) + hw_people($s, ['called','inside']) > $s['settings']['capacity']) hw_error('Ve sklepě není dost volných míst. Nejprve potvrďte odchod.', 409);
        $s['groups'][$id]['status'] = 'called'; $s['groups'][$id]['called'] = $now;
    }
    $s['callSeq']++; $s['calls'][] = ['seq' => $s['callSeq'], 'time' => $now, 'group' => $id, 'numbers' => $g['numbers'], 'repeat' => $repeat];
    $s['calls'] = array_slice($s['calls'], -50);
}
function hw_apply(array &$s, string $action, array $in, string $role, int $now): array {
    $allowed = ['sell'=>['cashier'], 'merge'=>['cashier'], 'cancel'=>['cashier'], 'next'=>['caller'], 'repeat'=>['caller'], 'missed'=>['caller'], 'return'=>['caller'], 'enter'=>['cellar'], 'exit'=>['cellar'], 'settings'=>[], 'pause'=>['caller'], 'close'=>[]];
    if (!array_key_exists($action, $allowed)) hw_error('Neznámá akce.');
    if ($role !== 'admin' && !in_array($role, $allowed[$action], true)) hw_error('Pro tuto akci nemáte oprávnění.', 403);
    if ($s['closed']) hw_error('Akce je uzavřená. Pro novou akci vytvořte nový přehled.', 409);
    $result = [];
    if ($action === 'sell') {
        $count = hw_int($in['count'] ?? 1, 1, 100, 'Počet vstupenek'); $together = ($in['together'] ?? true) === true;
        if ($together && $count > $s['settings']['capacity']) hw_error('Skupina je větší než kapacita sklepa. Prodejte je odděleně nebo upravte kapacitu.');
        if ($s['nextNumber'] + $count > 1000000 || count($s['sales']) + $count > 10000) hw_error('Byl dosažen limit 10 000 vstupenek nebo nejvyššího čísla.');
        $numbers = range($s['nextNumber'], $s['nextNumber'] + $count - 1); $s['nextNumber'] += $count;
        $chunks = $together ? [$numbers] : array_map(fn($n)=>[$n], $numbers);
        foreach ($chunks as $chunk) {
            $id = bin2hex(random_bytes(8)); $s['order']++;
            $s['groups'][$id] = ['id'=>$id, 'numbers'=>$chunk, 'order'=>$s['order'], 'status'=>'waiting', 'created'=>$now, 'called'=>null, 'entered'=>null, 'exited'=>null];
        }
        foreach ($numbers as $n) $s['sales'][] = ['number'=>$n, 'time'=>$now];
        $result = ['numbers'=>$numbers];
    } elseif ($action === 'merge') {
        $input = $in['numbers'] ?? [];
        if (!is_array($input) || count($input) < 2 || count($input) > 100) hw_error('Zadejte alespoň dvě různá prodaná čísla.');
        $selected = array_values(array_unique(array_map(fn($n)=>hw_int($n,1,999999,'Vstupenka'), $input))); $ids=[];
        foreach ($selected as $n) {
            $found = false;
            foreach ($s['groups'] as $id=>$g) if (in_array($n,$g['numbers'],true)) {
                if ($g['status'] !== 'waiting') hw_error("Vstupenka $n už není ve frontě. Spojit lze jen čekající skupiny.",409);
                $ids[$id]=true; $found=true; break;
            }
            if (!$found) hw_error("Vstupenka $n nebyla prodána nebo už byla spojena jinam.");
        }
        if (count($ids)<2) hw_error('Tato čísla už patří do stejné skupiny.');
        $numbers=[];$order=0;$created=0;
        foreach (array_keys($ids) as $id) { $g=$s['groups'][$id];$numbers=array_merge($numbers,$g['numbers']);$order=max($order,$g['order']);$created=max($created,$g['created']); }
        if (count($numbers)>$s['settings']['capacity']) hw_error('Celá spojená skupina překračuje kapacitu sklepa.');
        sort($numbers);foreach(array_keys($ids) as $id) unset($s['groups'][$id]);
        $id=bin2hex(random_bytes(8));$s['groups'][$id]=['id'=>$id,'numbers'=>$numbers,'order'=>$order,'status'=>'waiting','created'=>$created,'called'=>null,'entered'=>null,'exited'=>null];
        $result=['numbers'=>$numbers];
    } elseif ($action === 'next') {
        $queue = hw_queue($s); if (!$queue) hw_error('Ve frontě nikdo nečeká.',409);
        $id=$queue[0]['id'];hw_call($s,$id,$now);$result=['numbers'=>$queue[0]['numbers']];
    } elseif ($action === 'repeat') {
        hw_call($s,(string)($in['group']??''),$now,true);
    } elseif (in_array($action,['enter','exit','missed','return','cancel'],true)) {
        $id=(string)($in['group']??'');$g=hw_group($s,$id);
        $expected=['enter'=>'called','exit'=>'inside','missed'=>'called','return'=>'missed','cancel'=>'waiting'][$action];
        if($g['status']!==$expected)hw_error('Stav skupiny se změnil. Akce nebyla provedena dvakrát.',409);
        if($action==='enter') { $s['groups'][$id]['status']='inside';$s['groups'][$id]['entered']=$now; }
        if($action==='exit') { $s['groups'][$id]['status']='done';$s['groups'][$id]['exited']=$now; }
        if($action==='missed') $s['groups'][$id]['status']='missed';
        if($action==='return') { if(count($g['numbers'])>$s['settings']['capacity'])hw_error('Tato skupina je větší než současná kapacita sklepa.');$s['order']++;$s['groups'][$id]['status']='waiting';$s['groups'][$id]['order']=$s['order'];$s['groups'][$id]['created']=$now; }
        if($action==='cancel') $s['groups'][$id]['status']='cancelled';
    } elseif ($action==='settings') {
        $settings=hw_settings($in);$max=hw_people($s,['called','inside']);
        foreach($s['groups'] as $g)if($g['status']==='waiting')$max=max($max,count($g['numbers']));
        if($settings['capacity']<$max)hw_error('Kapacitu nelze snížit pod obsazená a rezervovaná místa nebo velikost čekající skupiny.');
        $s['settings']=$settings;
    } elseif ($action==='pause') $s['paused']=!$s['paused'];
    elseif ($action==='close') {
        if(hw_people($s,['called','inside'])>0)hw_error('Před uzavřením potvrďte odchod všech lidí a vyřešte vyvolané skupiny.',409);
        $s['closed']=true;
    }
    $s['revision']++;return $result;
}
function hw_snapshot(array $s, string $role, int $now, int $number=0): array {
    $durations=[];foreach($s['groups'] as $g)if($g['status']==='done' && $g['entered']!==null && $g['exited']>$g['entered'])$durations[]=$g['exited']-$g['entered'];
    $recent=array_slice($durations,-20);$duration=$recent?max(30,min(3600,(int)round(array_sum($recent)/count($recent)))):(int)round($s['settings']['minutes']*60);
    $capacity=$s['settings']['capacity'];$releases=[];
    foreach($s['groups'] as $g) if(in_array($g['status'],['called','inside'],true)) {
        $end=$g['status']==='inside'?max($now+30,$g['entered']+$duration):$now+$duration;
        for($i=0;$i<count($g['numbers']);$i++)$releases[]=$end;
    }
    $estimates=[];$queue=hw_queue($s);$cursor=$now;
    foreach($queue as $i=>$g) {
        $need=count($g['numbers']);$start=$cursor;
        while(true){$active=array_values(array_filter($releases,fn($t)=>$t>$start));if(count($active)+$need<=$capacity)break;sort($active);$start=$active[0];}
        $cursor=$start;$end=$start+$duration;for($j=0;$j<$need;$j++)$releases[]=$end;
        $estimates[$g['id']]=['position'=>$i+1,'eta'=>($s['paused']||$s['closed'])?null:$start];
    }
    $viewGroups=[];$lookup=null;$counts=array_fill_keys(['waiting','called','inside','done','missed','cancelled'],0);
    foreach($s['groups'] as $g){$counts[$g['status']]+=count($g['numbers']);$item=$g+($estimates[$g['id']]??['position'=>null,'eta'=>null]);$viewGroups[]=$item;if($number>0&&in_array($number,$g['numbers'],true))$lookup=$item;}
    $called=array_values(array_filter($viewGroups,fn($g)=>$g['status']==='called'));
    $publicQueue=array_slice(array_map(fn($g)=>$g+$estimates[$g['id']],$queue),0,20);
    $out=['id'=>$s['id'],'role'=>$role,'now'=>$now,'created'=>$s['created'],'expires'=>$s['expires'],'revision'=>$s['revision'],'settings'=>$s['settings'],'closed'=>$s['closed'],'paused'=>$s['paused'],'nextNumber'=>$s['nextNumber'],'counts'=>$counts,'sold'=>count($s['sales']),'queueGroups'=>count($queue),'called'=>$called,'queue'=>$publicQueue,'calls'=>array_slice($s['calls'],-20),'duration'=>$duration,'durationSamples'=>count($recent),'finish'=>($s['paused']||$s['closed']||!$releases)?null:max($releases),'ticket'=>$lookup];
    if($role!=='public')$out['groups']=$viewGroups;
    if($role==='admin'){
        $out['keys']=$s['keys'];$buckets=[];
        foreach($s['sales'] as $sale){$k=(int)(floor($sale['time']/900)*900);$buckets[$k]=($buckets[$k]??0)+1;}ksort($buckets);
        $out['salesBuckets']=array_map(fn($k,$v)=>['time'=>$k,'count'=>$v],array_keys($buckets),array_values($buckets));
    }
    return $out;
}
