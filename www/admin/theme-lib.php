<?php
declare(strict_types=1);
if (realpath((string)($_SERVER['SCRIPT_FILENAME'] ?? '')) === __FILE__) { http_response_code(403); exit; }

function av_theme_presets(): array {
    return [
        'original'=>['name'=>'ALTIVOX · tmavý','scheme'=>'dark','bg'=>'#071014','surface'=>'#0b1a20','raised'=>'#10252d','text'=>'#eef4f1','muted'=>'#a3b5bc','accent'=>'#c7ff3d','line'=>'#354b54'],
        'ocean'=>['name'=>'Oceán · modrý','scheme'=>'dark','bg'=>'#071321','surface'=>'#10233a','raised'=>'#16314f','text'=>'#f1f7ff','muted'=>'#abc2da','accent'=>'#72dbff','line'=>'#3c5874'],
        'paper'=>['name'=>'Papír · světlý','scheme'=>'light','bg'=>'#f5f4ee','surface'=>'#ffffff','raised'=>'#e9ece4','text'=>'#17251e','muted'=>'#4b5a52','accent'=>'#275d39','line'=>'#a7b4a8'],
        'sand'=>['name'=>'Písek · teplý','scheme'=>'light','bg'=>'#faf3e8','surface'=>'#fffaf2','raised'=>'#eee1cf','text'=>'#30261e','muted'=>'#675244','accent'=>'#92451e','line'=>'#c3aa8e'],
    ];
}
function av_theme_defaults(): array { return av_theme_presets()['original']; }
function av_theme_luminance(string $hex): float {
    $rgb=[]; foreach ([1,3,5] as $offset) { $v=hexdec(substr($hex,$offset,2))/255; $rgb[]=$v<=0.04045?$v/12.92:(($v+0.055)/1.055)**2.4; }
    return 0.2126*$rgb[0]+0.7152*$rgb[1]+0.0722*$rgb[2];
}
function av_theme_contrast(string $a,string $b): float { $x=av_theme_luminance($a);$y=av_theme_luminance($b);return (max($x,$y)+0.05)/(min($x,$y)+0.05); }
function av_theme_ink(string $color): string { return av_theme_contrast($color,'#ffffff')>av_theme_contrast($color,'#101610')?'#ffffff':'#101610'; }
function av_theme_validate(array $input): array {
    $out=av_theme_defaults();
    foreach (['bg','surface','raised','text','muted','accent','line'] as $key) {
        if (!isset($input[$key]) || !is_string($input[$key]) || !preg_match('/^#[a-fA-F0-9]{6}$/D',$input[$key])) throw new InvalidArgumentException('Barvy musí mít tvar #123ABC.');
        $out[$key]=strtolower($input[$key]);
    }
    if (!isset($input['scheme']) || !in_array($input['scheme'],['dark','light'],true)) throw new InvalidArgumentException('Vyberte světlé nebo tmavé schéma.');
    $out['scheme']=$input['scheme'];$out['name']='Vlastní schéma';
    foreach (['bg','surface','raised'] as $background) {
        if (av_theme_contrast($out['text'],$out[$background])<4.5) throw new InvalidArgumentException('Text je příliš podobný pozadí nebo panelům. Pro čitelnost je potřeba kontrast alespoň 4,5 : 1.');
        if (av_theme_contrast($out['muted'],$out[$background])<4.5) throw new InvalidArgumentException('Doplňkový text potřebuje proti pozadí i panelům kontrast alespoň 4,5 : 1.');
        if (av_theme_contrast($out['accent'],$out[$background])<3) throw new InvalidArgumentException('Zvýrazňovací barva potřebuje proti pozadí i panelům kontrast alespoň 3 : 1.');
    }
    return $out;
}
function av_theme_current(): array {
    try { $saved=av_read_json('appearance',[]);return $saved?av_theme_validate($saved):av_theme_defaults(); }
    catch (Throwable $e) { return av_theme_defaults(); }
}
function av_theme_css(array $theme): string {
    $vars=[];foreach (['bg','surface','raised','text','muted','accent','line'] as $key) $vars[]='--av-'.$key.':'.$theme[$key];
    $success=$theme['scheme']==='light'?'#1b683d':'#a5e8bd';$danger=$theme['scheme']==='light'?'#9d2323':'#ffaaa7';
    $vars[]='--av-success:'.$success;$vars[]='--av-success-ink:'.av_theme_ink($success);$vars[]='--av-danger:'.$danger;
    $vars[]='--av-scheme:'.$theme['scheme'];$vars[]='--av-accent-ink:'.av_theme_ink($theme['accent']);
    return ':root{'.implode(';',$vars).'}';
}
