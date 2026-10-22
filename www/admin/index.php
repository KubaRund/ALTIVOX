<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
av_require_auth();

$action = (string)($_GET['action'] ?? 'dashboard');
$typeFilter = (string)($_GET['type'] ?? '');
$flash = (string)($_GET['flash'] ?? '');
$records = av_records();

function av_admin_header(string $title): void {
    $user=av_escape((string)($_SESSION['av_user']??'admin'));
    ?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
    <title><?=av_escape($title)?> — ALTIVOX Control</title><link rel="stylesheet" href="assets/admin.css?v=20261022-theme1"></head><body>
    <button class="admin-menu" type="button" aria-label="Menu">MENU</button><aside class="sidebar">
      <a class="admin-brand" href="index.php"><span>▲</span>ALTIVOX<small>CONTROL CENTER</small></a>
      <nav>
        <a href="index.php">Dashboard</a>
        <a href="?action=records">Records</a>
        <a href="?action=edit">+ New record</a>
        <a href="?action=media">Media</a>
        <a href="?action=analytics">Analytics</a>
        <a href="appearance.php">Vzhled webu</a>
        <a href="?action=system">System</a>
      </nav>
      <div class="sidebar-bottom"><span><?=$user?></span><a href="/" target="_blank">PUBLIC SITE ↗</a><a href="logout.php">LOGOUT</a></div>
    </aside><main class="admin-main"><header class="topbar"><div><small>ALTIVOX / PRIVATE</small><h1><?=av_escape($title)?></h1></div><div class="top-actions"><a class="button" href="?action=edit">+ NEW RECORD</a></div></header>
    <?php
}
function av_admin_footer(): void { ?><script>document.querySelector('.admin-menu')?.addEventListener('click',()=>document.body.classList.toggle('admin-nav-open'));</script></main></body></html><?php }

if ($_SERVER['REQUEST_METHOD']==='POST') {
    av_check_csrf();
    $op=(string)($_POST['op']??'');
    if ($op==='save_record') {
        $id=trim((string)($_POST['id']??''));
        $title=trim((string)($_POST['title']??''));
        $type=trim((string)($_POST['type']??'field'));
        if (!in_array($type,['field','gear','objective','signal','lab','make'],true)) $type='field';
        $slug=av_slug((string)($_POST['slug']??$title));
        $existing=null; $idx=null;
        foreach ($records as $i=>$r) if (($r['id']??'')===$id) { $existing=$r; $idx=$i; break; }
        $photos=$existing['photos']??[];
        if (!empty($_FILES['photos']['name'][0])) {
            $photos=array_values(array_unique(array_merge($photos,av_upload_images($_FILES['photos'],$slug))));
        }
        $cover=trim((string)($_POST['cover']??($existing['cover']??'')));
        if ($cover==='' && $photos) $cover=$photos[0];
        $row=[
          'id'=>$id!==''?$id:bin2hex(random_bytes(6)),
          'type'=>$type,'title'=>$title!==''?$title:'Untitled','slug'=>$slug,
          'status'=>trim((string)($_POST['status']??'draft')),
          'published'=>isset($_POST['published']),
          'summary'=>trim((string)($_POST['summary']??'')),
          'body'=>trim((string)($_POST['body']??'')),
          'date'=>trim((string)($_POST['date']??'')),
          'location'=>trim((string)($_POST['location']??'')),
          'metrics'=>av_parse_metrics((string)($_POST['metrics']??'')),
          'tags'=>array_values(array_filter(array_map('trim',explode(',',(string)($_POST['tags']??''))))),
          'static_url'=>trim((string)($_POST['static_url']??'')),
          'brand'=>trim((string)($_POST['brand']??'')),
          'category'=>trim((string)($_POST['category']??'')),
          'role'=>trim((string)($_POST['role']??'')),
          'field_use'=>trim((string)($_POST['field_use']??'')),
          'strengths'=>trim((string)($_POST['strengths']??'')),
          'limitations'=>trim((string)($_POST['limitations']??'')),
          'tech_notes'=>trim((string)($_POST['tech_notes']??'')),
          'related'=>array_values(array_filter(array_map('trim',explode(',',(string)($_POST['related']??''))))),
          'cover'=>$cover,'photos'=>$photos,
          'created_at'=>$existing['created_at']??date(DATE_ATOM),'updated_at'=>date(DATE_ATOM),
        ];
        if ($idx===null) $records[]=$row; else $records[$idx]=$row;
        av_save_records($records);
        header('Location: ?action=edit&id='.rawurlencode($row['id']).'&flash=saved'); exit;
    }
    if ($op==='delete_record') {
        $id=(string)($_POST['id']??'');
        $records=array_values(array_filter($records,fn($r)=>($r['id']??'')!==$id));
        av_save_records($records);
        header('Location: ?action=records&flash=deleted'); exit;
    }
    if ($op==='delete_media') {
        $url=(string)($_POST['url']??'');
        if (str_starts_with($url,'/uploads/')) {
            $path=realpath(AV_ROOT.$url);
            $up=realpath(AV_UPLOAD_ROOT);
            if($path && $up && str_starts_with($path,$up) && is_file($path)) @unlink($path);
        }
        header('Location: ?action=media&flash=deleted'); exit;
    }
}

if ($action==='records') {
    av_admin_header('Records');
    if($flash): ?><div class="alert ok"><?=av_escape($flash)?></div><?php endif;
    $types=['all','field','gear','objective','signal','lab','make'];
    ?><div class="filters"><?php foreach($types as $t):?><a class="<?=($typeFilter===$t||($t==='all'&&$typeFilter===''))?'active':''?>" href="?action=records<?= $t==='all'?'':'&type='.$t?>"><?=strtoupper($t)?></a><?php endforeach;?></div>
    <div class="record-list"><?php
    $show=array_filter($records,fn($r)=>$typeFilter===''||($r['type']??'')===$typeFilter);
    usort($show,fn($a,$b)=>strcmp((string)($b['updated_at']??''),(string)($a['updated_at']??'')));
    foreach($show as $r): ?>
      <a class="record-row" href="?action=edit&id=<?=av_escape((string)$r['id'])?>">
        <span class="type"><?=av_escape(strtoupper((string)$r['type']))?></span>
        <div><b><?=av_escape((string)$r['title'])?></b><small><?=av_escape((string)$r['slug'])?></small></div>
        <span class="state"><?=av_escape(strtoupper((string)$r['status']))?></span>
        <span class="<?=!empty($r['published'])?'published':'draft'?>"><?=!empty($r['published'])?'PUBLIC':'DRAFT'?></span>
      </a>
    <?php endforeach;?></div><?php
    av_admin_footer(); exit;
}

if ($action==='edit') {
    $id=(string)($_GET['id']??''); $r=null;
    foreach($records as $row) if(($row['id']??'')===$id){$r=$row;break;}
    $r=$r??['id'=>'','type'=>'field','title'=>'','slug'=>'','status'=>'draft','published'=>false,'summary'=>'','body'=>'','date'=>'','location'=>'','metrics'=>[],'tags'=>[],'static_url'=>'','cover'=>'','photos'=>[],'brand'=>'','category'=>'','role'=>'','field_use'=>'','strengths'=>'','limitations'=>'','tech_notes'=>'','related'=>[]];
    av_admin_header($id?'Edit record':'New record');
    if($flash==='saved'):?><div class="alert ok">Uloženo.</div><?php endif;?>
    <form class="editor" method="post" enctype="multipart/form-data">
      <input type="hidden" name="csrf" value="<?=av_escape(av_csrf())?>"><input type="hidden" name="op" value="save_record"><input type="hidden" name="id" value="<?=av_escape((string)$r['id'])?>">
      <div class="editor-main">
        <section class="editor-block"><div class="editor-block-head"><span>01</span><h2>Identity</h2></div>
        <label>TYPE<select name="type" id="recordType"><?php foreach(['field','gear','objective','signal','lab','make'] as $t):?><option value="<?=$t?>" <?=($r['type']??'')===$t?'selected':''?>><?=strtoupper($t)?></option><?php endforeach;?></select></label>
        <label>TITLE<input name="title" value="<?=av_escape((string)$r['title'])?>" required></label>
        <div class="two"><label>SLUG<input name="slug" value="<?=av_escape((string)$r['slug'])?>" placeholder="auto-from-title"></label><label>STATUS<input name="status" value="<?=av_escape((string)$r['status'])?>" placeholder="completed / active / planned"></label></div>
        <div class="two"><label>BRAND / ORGANIZATION<input name="brand" value="<?=av_escape((string)($r['brand']??''))?>" placeholder="Samsung / Petzl / ČHMÚ…"></label><label>CATEGORY<input name="category" value="<?=av_escape((string)($r['category']??''))?>" placeholder="Field electronics / Alpine / Radio…"></label></div>
        <label>SUMMARY<textarea name="summary" rows="3"><?=av_escape((string)$r['summary'])?></textarea></label></section>

        <section class="editor-block"><div class="editor-block-head"><span>02</span><h2>Context</h2></div>
        <div class="two"><label>DATE<input type="date" name="date" value="<?=av_escape((string)$r['date'])?>"></label><label>LOCATION<input name="location" value="<?=av_escape((string)$r['location'])?>"></label></div>
        <label>ROLE / PURPOSE<input name="role" value="<?=av_escape((string)($r['role']??''))?>" placeholder="Primary field data logger / main sleep system…"></label>
        <label>FIELD USE<textarea name="field_use" rows="5" placeholder="Kde a k čemu se používá…"><?=av_escape((string)($r['field_use']??''))?></textarea></label>
        <label>METRICS <small>jeden řádek = KEY: VALUE</small><textarea name="metrics" rows="6"><?=av_escape(av_metrics_text((array)$r['metrics']))?></textarea></label></section>

        <section class="editor-block"><div class="editor-block-head"><span>03</span><h2>Experience</h2></div>
        <div class="two"><label>STRENGTHS<textarea name="strengths" rows="5" placeholder="- …"><?=av_escape((string)($r['strengths']??''))?></textarea></label><label>LIMITATIONS<textarea name="limitations" rows="5" placeholder="- …"><?=av_escape((string)($r['limitations']??''))?></textarea></label></div>
        <label>TECHNICAL NOTES<textarea name="tech_notes" rows="5"><?=av_escape((string)($r['tech_notes']??''))?></textarea></label>
        <label>BODY <small>## nadpis · ### podnadpis · - odrážka</small><textarea name="body" rows="14"><?=av_escape((string)$r['body'])?></textarea></label></section>

        <section class="editor-block"><div class="editor-block-head"><span>04</span><h2>Indexing</h2></div>
        <label>TAGS <small>oddělené čárkou</small><input name="tags" value="<?=av_escape(implode(', ',(array)$r['tags']))?>"></label>
        <label>RELATED SLUGS <small>oddělené čárkou</small><input name="related" value="<?=av_escape(implode(', ',(array)($r['related']??[])))?>" placeholder="fuscherkarkopf, galaxy-watch-ultra"></label></section>
      </div>
      <aside class="editor-side">
        <label class="publish-toggle"><input type="checkbox" name="published" <?=!empty($r['published'])?'checked':''?>><span>PUBLISHED</span></label>
        <label>STATIC URL <small>existující veřejná stránka; nové záznamy nech prázdné</small><input name="static_url" value="<?=av_escape((string)$r['static_url'])?>" placeholder="/expeditions/.../"></label>
        <label>COVER URL<input name="cover" value="<?=av_escape((string)$r['cover'])?>" placeholder="/uploads/..."></label>
        <label>UPLOAD PHOTOS<input type="file" name="photos[]" accept="image/jpeg,image/png,image/webp" multiple></label>
        <?php if(!empty($r['photos'])):?><div class="mini-gallery"><?php foreach($r['photos'] as $ph):?><img src="<?=av_escape($ph)?>" alt=""><?php endforeach;?></div><?php endif;?>
        <button class="primary" type="submit">SAVE + PUBLISH →</button>
        <?php if($id):?><a class="button full" target="_blank" href="<?=av_escape(av_record_url($r))?>">OPEN PUBLIC ↗</a><?php endif;?>
      </aside>
    </form>
    <?php if($id):?><form method="post" class="danger-zone" onsubmit="return confirm('Opravdu smazat záznam?')"><input type="hidden" name="csrf" value="<?=av_escape(av_csrf())?>"><input type="hidden" name="op" value="delete_record"><input type="hidden" name="id" value="<?=av_escape($id)?>"><button class="danger">DELETE RECORD</button></form><?php endif;
    av_admin_footer(); exit;
}

if ($action==='media') {
    av_admin_header('Media');
    $files=[];
    if(is_dir(AV_UPLOAD_ROOT)){
      $it=new RecursiveIteratorIterator(new RecursiveDirectoryIterator(AV_UPLOAD_ROOT,FilesystemIterator::SKIP_DOTS));
      foreach($it as $f) if($f->isFile() && !str_starts_with($f->getFilename(),'.')) $files[]=$f;
    }
    usort($files,fn($a,$b)=>$b->getMTime()<=>$a->getMTime());
    ?><div class="media-grid"><?php foreach($files as $f):$url=str_replace(AV_ROOT,'',$f->getPathname());?>
    <figure><img src="<?=av_escape($url)?>" loading="lazy"><figcaption><span><?=av_escape($f->getFilename())?></span><small><?=av_escape(av_human_bytes($f->getSize()))?></small>
    <form method="post" onsubmit="return confirm('Smazat soubor?')"><input type="hidden" name="csrf" value="<?=av_escape(av_csrf())?>"><input type="hidden" name="op" value="delete_media"><input type="hidden" name="url" value="<?=av_escape($url)?>"><button>DELETE</button></form></figcaption></figure>
    <?php endforeach;?></div><?php av_admin_footer(); exit;
}

if ($action==='analytics') {
    av_admin_header('Analytics');
    $a=av_read_json('analytics',[]); $today=date('Y-m-d'); $todayRow=$a[$today]??[];
    $dates=array_keys($a); rsort($dates); $dates=array_slice($dates,0,30);
    $views30=0;$vis30=[];$pages=[];
    foreach($dates as $d){$row=$a[$d]??[];$views30+=(int)($row['pageviews']??0);foreach(($row['visitors']??[]) as $v=>$x)$vis30[$v]=1;foreach(($row['pages']??[]) as $p=>$c)$pages[$p]=($pages[$p]??0)+(int)$c;}
    arsort($pages);
    $live=av_read_json('live',[]);$cut=time()-1800;$liveNow=array_filter($live,fn($x)=>(int)($x['ts']??0)>$cut);
    ?><div class="stat-grid">
    <div><small>TODAY / VIEWS</small><b><?=number_format((int)($todayRow['pageviews']??0),0,',',' ')?></b></div>
    <div><small>TODAY / VISITORS</small><b><?=count($todayRow['visitors']??[])?></b></div>
    <div><small>30 DAYS / VIEWS</small><b><?=number_format($views30,0,',',' ')?></b></div>
    <div><small>LIVE / 30 MIN</small><b><?=count($liveNow)?></b></div>
    </div><section class="panel"><h2>TOP PAGES / 30 DAYS</h2><div class="table"><?php $i=0;foreach($pages as $p=>$c){if(++$i>15)break;?><div><span><?=av_escape($p)?></span><b><?=$c?></b></div><?php }?></div></section>
    <section class="panel"><h2>DAILY</h2><div class="table"><?php foreach(array_slice($dates,0,14) as $d):$r=$a[$d];?><div><span><?=$d?></span><b><?=intval($r['pageviews']??0)?> views / <?=count($r['visitors']??[])?> visitors</b></div><?php endforeach;?></div></section>
    <?php av_admin_footer(); exit;
}

if ($action==='system') {
    av_admin_header('System');
    $bytes=av_storage_bytes(AV_UPLOAD_ROOT);
    $phpok=version_compare(PHP_VERSION,'8.1','>=');
    ?><div class="stat-grid">
      <div><small>PHP</small><b><?=av_escape(PHP_VERSION)?></b></div>
      <div><small>RECORDS</small><b><?=count($records)?></b></div>
      <div><small>UPLOAD STORAGE</small><b><?=av_escape(av_human_bytes($bytes))?></b></div>
      <div><small>DATA DIR</small><b><?=is_writable(AV_DATA)?'WRITE OK':'CHECK'?></b></div>
    </div>
    <section class="panel"><h2>CONTROL CENTER</h2><div class="table">
      <div><span>PHP 8.1+</span><b><?=$phpok?'OK':'UPDATE REQUIRED'?></b></div>
      <div><span>Fileinfo</span><b><?=class_exists('finfo')?'OK':'MISSING'?></b></div>
      <div><span>GD image optimization</span><b><?=function_exists('imagewebp')?'WEBP READY':'FALLBACK ORIGINAL'?></b></div>
      <div><span>HTTPS</span><b><?=(!empty($_SERVER['HTTPS'])&&$_SERVER['HTTPS']!=='off')?'ON':'CHECK'?></b></div>
    </div></section>
    <section class="panel"><h2>BACKUP</h2><p>Stáhni JSON export obsahu a statistik. Fotografie zůstávají v /uploads.</p><a class="button" href="export.php">DOWNLOAD JSON BACKUP ↓</a></section>
    <?php av_admin_footer(); exit;
}

// Dashboard
av_admin_header('Dashboard');
$counts=[];foreach($records as $r){$t=$r['type']??'other';$counts[$t]=($counts[$t]??0)+1;}
$a=av_read_json('analytics',[]);$today=date('Y-m-d');$tr=$a[$today]??[];
$live=av_read_json('live',[]);$liveNow=array_filter($live,fn($x)=>(int)($x['ts']??0)>time()-1800);
?>
<div class="stat-grid">
  <div><small>FIELD NOTES</small><b><?=intval($counts['field']??0)?></b></div>
  <div><small>GEAR RECORDS</small><b><?=intval($counts['gear']??0)?></b></div>
  <div><small>VISITORS TODAY</small><b><?=count($tr['visitors']??[])?></b></div>
  <div><small>LIVE / 30 MIN</small><b><?=count($liveNow)?></b></div>
</div>
<div class="dashboard-grid">
<section class="panel"><div class="panel-head"><h2>QUICK ACTIONS</h2></div>
<div class="quick-actions">
<a href="?action=edit&type=field">+ NEW FIELD NOTE</a><a href="?action=edit&type=gear">+ NEW GEAR</a><a href="?action=edit&type=objective">+ NEW OBJECTIVE</a><a href="?action=media">MEDIA LIBRARY</a>
</div></section>
<section class="panel"><div class="panel-head"><h2>LATEST RECORDS</h2><a href="?action=records">ALL →</a></div>
<div class="table"><?php $last=$records;usort($last,fn($a,$b)=>strcmp((string)($b['updated_at']??''),(string)($a['updated_at']??'')));foreach(array_slice($last,0,7) as $r):?><a href="?action=edit&id=<?=av_escape((string)$r['id'])?>"><span><?=av_escape(strtoupper((string)$r['type']))?> / <?=av_escape((string)$r['title'])?></span><b><?=av_escape(strtoupper((string)$r['status']))?></b></a><?php endforeach;?></div>
</section></div>
<section class="panel"><div class="panel-head"><h2>SYSTEM STATUS</h2><span class="green">● ONLINE</span></div>
<div class="table"><div><span>PUBLIC SITE</span><b>ALTIVOX.CZ</b></div><div><span>CONTENT STORE</span><b><?=count($records)?> RECORDS</b></div><div><span>ANALYTICS</span><b>FIRST-PARTY / CONSENT</b></div><div><span>UPLOADS</span><b><?=av_escape(av_human_bytes(av_storage_bytes(AV_UPLOAD_ROOT)))?></b></div></div></section>
<?php av_admin_footer();
