<?php
declare(strict_types=1);
require __DIR__.'/core.php';
header('Content-Type: application/json; charset=utf-8');header('Cache-Control: no-store');header('X-Content-Type-Options: nosniff');header('Referrer-Policy: no-referrer');
function output(array $data,int $code=200): never { http_response_code($code);echo json_encode($data,JSON_UNESCAPED_UNICODE|JSON_INVALID_UTF8_SUBSTITUTE|JSON_THROW_ON_ERROR);exit; }
function event_lock($handle, int $mode): void {
    if(!$handle) hw_error('Úložiště není dostupné.',503);
    $deadline=microtime(true)+2;
    do { if(flock($handle,$mode|LOCK_NB)) return; usleep(20000); } while(microtime(true)<$deadline);
    hw_error('Akce je zaneprázdněná. Zkuste za chvíli.',503);
}
try {
    if(($_SERVER['REQUEST_METHOD']??'')!=='POST')hw_error('Použijte POST.',405);
    if((int)($_SERVER['CONTENT_LENGTH']??0)>32768)hw_error('Požadavek je příliš velký.',413);
    if(($_SERVER['HTTP_SEC_FETCH_SITE']??'')==='cross-site')hw_error('Nepovolený původ.',403);
    if(isset($_SERVER['HTTP_ORIGIN'])) {
        $origin=parse_url($_SERVER['HTTP_ORIGIN']);$host=strtolower((string)($origin['host']??''));if(isset($origin['port']))$host.=':'.$origin['port'];
        if(!in_array($origin['scheme']??'',['http','https'],true)||$host!==strtolower($_SERVER['HTTP_HOST']??''))hw_error('Nepovolený původ.',403);
    }
    $raw=file_get_contents('php://input',false,null,0,32769);if(strlen($raw)>32768)hw_error('Požadavek je příliš velký.',413);
    $in=json_decode($raw,true,32,JSON_THROW_ON_ERROR);if(!is_array($in))hw_error('Neplatný požadavek.');
    $action=(string)($in['action']??'');$now=time();
    $base=getenv('ALTIVOX_HALLOWEEN_DATA')?:dirname(__DIR__,2).'/.altivox-halloween';
    if(!is_dir($base)&&!@mkdir($base,0700,true))hw_error('Úložiště akce nelze vytvořit.',503);
    if($action==='create') {
        $guard=fopen($base.'/create.lock','c+');event_lock($guard,LOCK_EX);
        $files=glob($base.'/event-*.json')?:[];if(count($files)>=300)hw_error('Byl dosažen limit uložených akcí.',429);
        $ip=hash('sha256',($_SERVER['REMOTE_ADDR']??'local').__DIR__);$ratePath=$base.'/rates.json';$rates=json_decode(@file_get_contents($ratePath)?:'{}',true)?:[];
        foreach($rates as $k=>$v)if($v['until']<$now)unset($rates[$k]);$rate=$rates[$ip]??['count'=>0,'until'=>$now+3600];
        if($rate['count']>=12)hw_error('Z této sítě lze vytvořit nejvýše 12 akcí za hodinu.',429);
        $state=hw_new($in,$now);$path=$base.'/event-'.$state['id'].'.json';
        if(file_put_contents($path,json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE),LOCK_EX)===false)hw_error('Akci nelze uložit.',503);@chmod($path,0600);
        $rate['count']++;$rates[$ip]=$rate;file_put_contents($ratePath,json_encode($rates),LOCK_EX);flock($guard,LOCK_UN);fclose($guard);
        output(['state'=>hw_snapshot($state,'admin',$now)]);
    }
    $id=(string)($in['id']??'');if(!preg_match('/^[a-f0-9]{24}$/D',$id))hw_error('Neplatný odkaz akce.',404);
    $path=$base.'/event-'.$id.'.json';if(!is_file($path))hw_error('Akce nebyla nalezena. Ověřte odkaz.',404);
    $lock=fopen($base.'/event-'.$id.'.lock','c+');event_lock($lock,$action==='read'?LOCK_SH:LOCK_EX);
    $state=json_decode(file_get_contents($path),true,64,JSON_THROW_ON_ERROR);if($state['expires']<$now)hw_error('Platnost akce po 30 dnech skončila.',410);
    $role=hw_role($state,(string)($in['key']??''));$result=[];
    if($action==='read'&&($state['version']??1)===2&&($in['knownRevision']??null)===$state['revision']&&($in['knownRole']??null)===$role) {
        flock($lock,LOCK_UN);fclose($lock);output(['unchanged'=>true,'now'=>$now,'role'=>$role]);
    }
    if($action!=='read') {
        $request=(string)($in['request']??'');if(!preg_match('/^[a-zA-Z0-9-]{16,80}$/D',$request))hw_error('Chybí identifikátor operace. Obnovte stránku.');
        $fingerprint=hash('sha256',json_encode([$action,$role,array_diff_key($in,array_flip(['request','key']))],JSON_THROW_ON_ERROR));
        if(isset($state['requests'][$request])) {
            $previous=$state['requests'][$request];if($previous['fingerprint']!==$fingerprint)hw_error('Identifikátor operace byl použit jinak.',409);$result=$previous['result'];
        } else {
            $result=hw_apply($state,$action,$in,$role,$now);$state['requests'][$request]=['fingerprint'=>$fingerprint,'result'=>$result];
            if(count($state['requests'])>1000)$state['requests']=array_slice($state['requests'],-1000,null,true);
            $encoded=json_encode($state,JSON_THROW_ON_ERROR|JSON_UNESCAPED_UNICODE);$tmp=$path.'.'.bin2hex(random_bytes(6)).'.tmp';
            if(strlen($encoded)>8*1024*1024)hw_error('Akce dosáhla limitu dat. Založte další akci.',429);
            if(file_put_contents($tmp,$encoded,LOCK_EX)===false)hw_error('Akci nelze uložit.',503);@chmod($tmp,0600);
            $replaced=false;
            for($attempt=0;$attempt<10;$attempt++) { if(@rename($tmp,$path)){$replaced=true;break;} usleep(20000); }
            if(!$replaced){@unlink($tmp);hw_error('Změnu nelze potvrdit v úložišti.',503);}
        }
    }
    flock($lock,LOCK_UN);fclose($lock);$snapshot=hw_snapshot($state,$role,$now,(int)($in['number']??0));output(['state'=>$snapshot,'result'=>$result]);
} catch(DomainException $e) { output(['error'=>$e->getMessage()],$e->getCode()>=400?$e->getCode():400); }
catch(JsonException $e) { output(['error'=>'Data nelze načíst. Obnovte stránku; pokud se chyba opakuje, kontaktujte správce.'],400); }
catch(Throwable $e) { error_log('ALTIVOX Halloween: '.$e->getMessage());output(['error'=>'Server nyní nemůže požadavek dokončit. Zkuste obnovit přehled.'],503); }
