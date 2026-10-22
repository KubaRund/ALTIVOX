<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: public, max-age=60');
$root=realpath(__DIR__.'/..');
$dirs=[
  ['path'=>$root.'/assets/photos','url'=>'/assets/photos'],
  ['path'=>$root.'/uploads','url'=>'/uploads'],
];
$allowed=['jpg','jpeg','png','webp'];
$out=[];
foreach($dirs as $d){
  if(!is_dir($d['path'])) continue;
  $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator($d['path'],FilesystemIterator::SKIP_DOTS));
  foreach($it as $f){
    if(!$f->isFile()) continue;
    $ext=strtolower($f->getExtension());
    if(!in_array($ext,$allowed,true)) continue;
    $rel=str_replace('\\','/',substr($f->getPathname(),strlen($d['path'])));
    $url=$d['url'].$rel;
    $low=strtolower($url);
    $category='other';
    if(str_contains($low,'/expeditions/'))$category='field';
    elseif(str_contains($low,'/gear/'))$category='gear';
    elseif(str_contains($low,'/lab/'))$category='lab';
    elseif(str_contains($low,'/tech/'))$category='signal';
    elseif(str_contains($low,'/water/'))$category='water';
    elseif(str_starts_with($low,'/uploads/'))$category='uploads';
    $parts=array_values(array_filter(explode('/',$rel)));
    $label=count($parts)>1?$parts[count($parts)-2]:$f->getBasename('.'.$ext);
    if($label==='web' && count($parts)>2)$label=$parts[count($parts)-3];
    $out[]=['url'=>$url,'category'=>$category,'label'=>str_replace('-',' ',$label),'mtime'=>$f->getMTime()];
  }
}
usort($out,fn($a,$b)=>$b['mtime']<=>$a['mtime']);
echo json_encode(['ok'=>true,'count'=>count($out),'images'=>$out],JSON_UNESCAPED_UNICODE|JSON_UNESCAPED_SLASHES);
