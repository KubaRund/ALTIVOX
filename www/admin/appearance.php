<?php
declare(strict_types=1);
require __DIR__.'/bootstrap.php';
av_require_auth();
require __DIR__.'/theme-lib.php';
header('Cache-Control: no-store');
$theme=av_theme_current();$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    av_check_csrf();
    try {
        $theme=($_POST['operation']??'')==='reset'?av_theme_defaults():av_theme_validate($_POST);
        av_write_json('appearance',$theme);
        header('Location: appearance.php?saved=1');exit;
    } catch (InvalidArgumentException $e) { $error=$e->getMessage(); }
    catch (Throwable $e) { http_response_code(500);$error='Schéma se nepodařilo uložit. Původní nastavení zůstalo zachované. Zkuste to znovu.';error_log('ALTIVOX appearance save failed: '.$e->getMessage()); }
}
$labels=['bg'=>'Pozadí stránky','surface'=>'Panely a karty','raised'=>'Zvýšené plochy','text'=>'Hlavní text','muted'=>'Doplňkový text','accent'=>'Zvýraznění a tlačítka','line'=>'Čáry a okraje'];
?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Vzhled webu — ALTIVOX</title><link rel="stylesheet" href="assets/admin.css?v=20261022-theme1"><link rel="stylesheet" href="assets/appearance.css"></head>
<body><button class="admin-menu" type="button" aria-label="Menu">MENU</button><aside class="sidebar"><a class="admin-brand" href="index.php"><span>▲</span>ALTIVOX<small>CONTROL CENTER</small></a><nav><a href="index.php">Dashboard</a><a href="index.php?action=records">Records</a><a href="index.php?action=media">Media</a><a href="index.php?action=analytics">Analytics</a><a href="appearance.php" class="active" aria-current="page">Vzhled webu</a><a href="index.php?action=system">System</a></nav><div class="sidebar-bottom"><a href="/" target="_blank" rel="noopener">OTEVŘÍT WEB ↗</a><a href="logout.php">ODHLÁSIT</a></div></aside>
<main class="admin-main"><header class="topbar"><div><small>ALTIVOX / NASTAVENÍ</small><h1>Vzhled webu</h1></div></header>
<p class="appearance-intro">Jedno barevné schéma pro rubriky, články i veřejné nástroje. Náhled se mění hned; návštěvníci změnu uvidí až po uložení.</p>
<?php if(isset($_GET['saved'])):?><div class="alert ok" role="status">Schéma je uložené a používá ho celý web. Již otevřenou stránku obnovte.</div><?php endif;?>
<?php if($error):?><div class="alert error" role="alert"><?=av_escape($error)?></div><?php endif;?>
<div class="appearance-layout"><form id="appearance-form" method="post" class="appearance-controls" data-presets="<?=av_escape((string)json_encode(av_theme_presets()))?>">
<input type="hidden" name="csrf" value="<?=av_escape(av_csrf())?>"><input type="hidden" name="operation" value="save">
<section class="editor-block"><h2>Vyberte základ</h2><div class="appearance-presets"><?php foreach(av_theme_presets() as $id=>$preset):?><button type="button" class="appearance-preset" data-preset="<?=$id?>"><?=av_escape($preset['name'])?></button><?php endforeach;?></div></section>
<section class="editor-block"><h2>Upravte barvy</h2><label>Režim prvků<select name="scheme" id="appearance-scheme"><option value="dark" <?=$theme['scheme']==='dark'?'selected':''?>>Tmavý</option><option value="light" <?=$theme['scheme']==='light'?'selected':''?>>Světlý</option></select></label>
<?php foreach($labels as $key=>$label):?><div class="appearance-color"><label for="color-<?=$key?>"><?=av_escape($label)?></label><input type="color" id="color-<?=$key?>" value="<?=av_escape($theme[$key])?>" data-color="<?=$key?>" aria-label="<?=av_escape($label)?> · výběr barvy"><input id="hex-<?=$key?>" name="<?=$key?>" class="hex-input" value="<?=av_escape($theme[$key])?>" pattern="#[a-fA-F0-9]{6}" maxlength="7" required aria-label="<?=av_escape($label)?> · hexadecimální kód"></div><?php endforeach;?></section>
<p id="contrast-status" class="appearance-contrast" role="status" aria-live="polite"></p><button class="primary" type="submit" id="save-appearance">Uložit schéma pro celý web</button>
</form><section class="appearance-preview-wrap"><div class="appearance-preview-title"><h2>Živý náhled</h2><span>Neuložené změny</span></div><div id="appearance-preview" class="appearance-preview"><header><b>▲ ALTIVOX</b><span>FIELD · GEAR · SIGNAL · LAB</span></header><div class="preview-content"><small>JEDEN WEB. SPOLEČNÝ VZHLED.</small><h2>Venku i ve třídě.</h2><p>Výpravy, měření, technika a výuka na jednom místě.</p><div class="preview-card"><small>MOJE PROJEKTY</small><h3>Od nápadu k vlastní zkušenosti</h3><p>Doplňkový text, čitelné karty a jednotné ovládání.</p><span class="preview-button">Otevřít článek ↗</span></div><div class="preview-raised">Zvýšená plocha · přehled nebo nástroj</div><a href="#appearance-preview">Odkaz a zvýraznění →</a></div><footer>ALTIVOX · FIELD / SIGNAL / MAKE</footer></div><p class="appearance-help">Barvy významových stavů, grafů a fotografií se nepřebarvují. U nástrojů zůstává zachované také ovládání.</p></section></div>
<form method="post" class="appearance-reset"><input type="hidden" name="csrf" value="<?=av_escape(av_csrf())?>"><input type="hidden" name="operation" value="reset"><button class="button" type="submit">Obnovit výchozí ALTIVOX</button></form></main><script src="assets/appearance.js"></script></body></html>
