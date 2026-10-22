<?php
declare(strict_types=1);
if (!function_exists('str_starts_with')) { function str_starts_with(string $h,string $n): bool { return $n==='' || strpos($h,$n)===0; } }
if (!function_exists('str_contains')) { function str_contains(string $h,string $n): bool { return $n==='' || strpos($h,$n)!==false; } }

const AV_ROOT = __DIR__ . '/..';
const AV_DATA = __DIR__ . '/data';
const AV_UPLOAD_ROOT = AV_ROOT . '/uploads';

function av_config_path(): string { return AV_DATA . '/config.php'; }
function av_is_installed(): bool { return is_file(av_config_path()); }

function av_config(): array {
    static $cfg = null;
    if ($cfg !== null) return $cfg;
    if (!av_is_installed()) return [];
    $cfg = require av_config_path();
    return is_array($cfg) ? $cfg : [];
}

function av_boot_session(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
    session_name('ALTIVOX_ADMIN');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/admin/',
        'secure' => $secure,
        'httponly' => true,
        'samesite' => 'Strict',
    ]);
    session_start();
}

function av_escape(string $s): string {
    return htmlspecialchars($s, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function av_slug(string $s): string {
    $s = trim(mb_strtolower($s, 'UTF-8'));
    $trans = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $s);
    if ($trans !== false) $s = $trans;
    $s = preg_replace('/[^a-z0-9]+/', '-', $s) ?? '';
    return trim($s, '-') ?: 'record-' . date('Ymd-His');
}

function av_read_json(string $name, array $default = []): array {
    $path = AV_DATA . '/' . $name . '.json';
    if (!is_file($path)) return $default;
    $raw = file_get_contents($path);
    if ($raw === false || $raw === '') return $default;
    $data = json_decode($raw, true);
    return is_array($data) ? $data : $default;
}

function av_write_json(string $name, array $data): void {
    if (!is_dir(AV_DATA)) mkdir(AV_DATA, 0750, true);
    $path = AV_DATA . '/' . $name . '.json';
    $tmp = $path . '.tmp.' . bin2hex(random_bytes(4));
    $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    if ($json === false) throw new RuntimeException('JSON encode failed.');
    $fh = fopen($tmp, 'wb');
    if (!$fh) throw new RuntimeException('Cannot open temp data file.');
    if (!flock($fh, LOCK_EX)) { fclose($fh); throw new RuntimeException('Cannot lock data file.'); }
    fwrite($fh, $json);
    fflush($fh);
    flock($fh, LOCK_UN);
    fclose($fh);
    chmod($tmp, 0640);
    if (!rename($tmp, $path)) { @unlink($tmp); throw new RuntimeException('Cannot replace data file.'); }
}

function av_records(): array { return av_read_json('records', []); }
function av_save_records(array $r): void { av_write_json('records', array_values($r)); }

function av_record_url(array $r): string {
    $static = trim((string)($r['static_url'] ?? ''));
    if ($static !== '') return $static;
    return '/record.php?type=' . rawurlencode((string)$r['type']) . '&slug=' . rawurlencode((string)$r['slug']);
}

function av_find_record(string $type, string $slug): ?array {
    foreach (av_records() as $r) {
        if (($r['type'] ?? '') === $type && ($r['slug'] ?? '') === $slug) return $r;
    }
    return null;
}

function av_csrf(): string {
    av_boot_session();
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(24));
    return (string)$_SESSION['csrf'];
}

function av_check_csrf(): void {
    av_boot_session();
    $got = (string)($_POST['csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
    if ($got === '' || !hash_equals((string)($_SESSION['csrf'] ?? ''), $got)) {
        http_response_code(403);
        exit('Invalid CSRF token.');
    }
}

function av_auth_ok(): bool {
    av_boot_session();
    return !empty($_SESSION['av_auth']) && $_SESSION['av_auth'] === true;
}

function av_require_auth(): void {
    if (!av_is_installed()) {
        header('Location: setup.php');
        exit;
    }
    if (!av_auth_ok()) {
        $next = rawurlencode($_SERVER['REQUEST_URI'] ?? '/admin/');
        header('Location: login.php?next=' . $next);
        exit;
    }
}

function av_login_limiter_key(): string {
    $cfg = av_config();
    $secret = (string)($cfg['secret'] ?? 'altivox');
    $ip = (string)($_SERVER['REMOTE_ADDR'] ?? 'unknown');
    return hash_hmac('sha256', $ip, $secret);
}

function av_login_attempt_allowed(): bool {
    $all = av_read_json('login_attempts', []);
    $key = av_login_limiter_key();
    $now = time();
    $row = $all[$key] ?? ['count'=>0,'first'=>$now];
    if (($now - (int)$row['first']) > 900) return true;
    return (int)$row['count'] < 8;
}

function av_login_attempt(bool $success): void {
    $all = av_read_json('login_attempts', []);
    $key = av_login_limiter_key();
    if ($success) {
        unset($all[$key]);
    } else {
        $now = time();
        $row = $all[$key] ?? ['count'=>0,'first'=>$now];
        if (($now - (int)$row['first']) > 900) $row = ['count'=>0,'first'=>$now];
        $row['count'] = (int)$row['count'] + 1;
        $all[$key] = $row;
    }
    av_write_json('login_attempts', $all);
}

function av_render_text(string $text): string {
    $lines = preg_split('/\R/', trim($text));
    if (!$lines) return '';
    $out = '';
    $inList = false;
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '') { if ($inList) { $out .= '</ul>'; $inList=false; } continue; }
        if (str_starts_with($line, '## ')) {
            if ($inList) { $out .= '</ul>'; $inList=false; }
            $out .= '<h2>' . av_escape(substr($line,3)) . '</h2>';
        } elseif (str_starts_with($line, '### ')) {
            if ($inList) { $out .= '</ul>'; $inList=false; }
            $out .= '<h3>' . av_escape(substr($line,4)) . '</h3>';
        } elseif (str_starts_with($line, '- ')) {
            if (!$inList) { $out .= '<ul>'; $inList=true; }
            $out .= '<li>' . av_escape(substr($line,2)) . '</li>';
        } else {
            if ($inList) { $out .= '</ul>'; $inList=false; }
            $out .= '<p>' . nl2br(av_escape($line)) . '</p>';
        }
    }
    if ($inList) $out .= '</ul>';
    return $out;
}

function av_parse_metrics(string $text): array {
    $out = [];
    foreach (preg_split('/\R/', trim($text)) ?: [] as $line) {
        if (!str_contains($line, ':')) continue;
        [$k,$v] = array_map('trim', explode(':', $line, 2));
        if ($k !== '' && $v !== '') $out[$k] = $v;
    }
    return $out;
}

function av_metrics_text(array $metrics): string {
    $rows = [];
    foreach ($metrics as $k=>$v) $rows[] = $k . ': ' . $v;
    return implode("\n", $rows);
}

function av_upload_images(array $files, string $slug): array {
    $urls = [];
    $max = 20 * 1024 * 1024;
    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $allowed = [
        'image/jpeg'=>'jpg',
        'image/png'=>'png',
        'image/webp'=>'webp',
    ];
    $year = date('Y');
    $month = date('m');
    $dir = AV_UPLOAD_ROOT . '/' . $year . '/' . $month . '/' . av_slug($slug);
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) throw new RuntimeException('Cannot create upload directory.');

    $names = $files['name'] ?? [];
    if (!is_array($names)) return [];
    for ($i=0; $i<count($names); $i++) {
        if (($files['error'][$i] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) continue;
        $tmp = (string)$files['tmp_name'][$i];
        $size = (int)$files['size'][$i];
        if ($size <= 0 || $size > $max) continue;
        $mime = $finfo->file($tmp);
        if (!isset($allowed[$mime])) continue;
        $ext = $allowed[$mime];
        $base = sprintf('%02d-%s', $i+1, bin2hex(random_bytes(4)));
        $target = $dir . '/' . $base . '.' . $ext;

        // If GD is available, normalize JPEG/PNG to WebP for the public site.
        $converted = false;
        if ($mime !== 'image/webp' && function_exists('imagewebp')) {
            $img = null;
            if ($mime === 'image/jpeg' && function_exists('imagecreatefromjpeg')) $img = @imagecreatefromjpeg($tmp);
            if ($mime === 'image/png' && function_exists('imagecreatefrompng')) $img = @imagecreatefrompng($tmp);
            if ($img) {
                $w = imagesx($img); $h = imagesy($img);
                $maxSide = 2200;
                if (max($w,$h) > $maxSide && function_exists('imagescale')) {
                    $scale = $maxSide / max($w,$h);
                    $nw = max(1,(int)round($w*$scale));
                    $nh = max(1,(int)round($h*$scale));
                    $scaled = imagescale($img,$nw,$nh,IMG_BICUBIC);
                    if ($scaled) { imagedestroy($img); $img=$scaled; }
                }
                $target = $dir . '/' . $base . '.webp';
                $converted = @imagewebp($img, $target, 82);
                imagedestroy($img);
            }
        }
        if (!$converted) {
            if (!move_uploaded_file($tmp, $target)) continue;
        }
        @chmod($target, 0644);
        $urls[] = str_replace(AV_ROOT, '', $target);
    }
    return $urls;
}

function av_storage_bytes(string $path): int {
    if (!is_dir($path)) return 0;
    $sum = 0;
    $it = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($path, FilesystemIterator::SKIP_DOTS));
    foreach ($it as $file) if ($file->isFile()) $sum += $file->getSize();
    return $sum;
}

function av_human_bytes(int $bytes): string {
    $u=['B','KB','MB','GB','TB']; $i=0; $v=$bytes;
    while ($v>=1024 && $i<count($u)-1) { $v/=1024; $i++; }
    return number_format($v, $i===0?0:1, ',', ' ') . ' ' . $u[$i];
}
