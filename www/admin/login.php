<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';
if (!av_is_installed()) { header('Location: setup.php'); exit; }
av_boot_session();
if (av_auth_ok()) { header('Location: index.php'); exit; }
$error='';
if ($_SERVER['REQUEST_METHOD']==='POST') {
    if (!av_login_attempt_allowed()) {
        $error='Příliš mnoho pokusů. Zkus to znovu za 15 minut.';
    } else {
        $cfg=av_config();
        $u=trim((string)($_POST['username']??''));
        $p=(string)($_POST['password']??'');
        $ok=hash_equals((string)$cfg['username'],$u) && password_verify($p,(string)$cfg['password_hash']);
        av_login_attempt($ok);
        if ($ok) {
            session_regenerate_id(true);
            $_SESSION['av_auth']=true;
            $_SESSION['av_user']=$u;
            $_SESSION['csrf']=bin2hex(random_bytes(24));
            $next=(string)($_GET['next']??'/admin/');
            if (!str_starts_with($next,'/admin/')) $next='/admin/';
            header('Location: '.$next); exit;
        }
        $error='Nesprávné přihlášení.';
    }
}
?><!doctype html><html lang="cs"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title>ALTIVOX / LOGIN</title><link rel="stylesheet" href="assets/admin.css"></head>
<body class="auth-body"><main class="auth-card">
<div class="admin-kicker">ALTIVOX / PRIVATE</div><h1>CONTROL<br>CENTER.</h1>
<p>Field archive administration.</p>
<?php if(isset($_GET['setup'])):?><div class="alert ok">Setup dokončen. Přihlas se.</div><?php endif;?>
<?php if($error):?><div class="alert error"><?=av_escape($error)?></div><?php endif;?>
<form method="post"><label>USER<input name="username" autocomplete="username" required autofocus></label>
<label>PASSWORD<input type="password" name="password" autocomplete="current-password" required></label>
<button class="primary">AUTHENTICATE →</button></form>
<a class="back-link" href="/">← BACK TO ALTIVOX</a>
</main></body></html>
