<?php
declare(strict_types=1);
// ALTIVOX classroom rooms. No database or accounts; all rooms expire after 24 hours.
header('Cache-Control: no-store');
header('X-Content-Type-Options: nosniff');
header('Referrer-Policy: no-referrer');
function fail(string $message, int $status = 400): never {
    http_response_code($status); header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['error' => $message], JSON_UNESCAPED_UNICODE); exit;
}
function respond(array $value): never {
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode($value, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE); exit;
}
function strval_limit(mixed $value, int $limit): string {
    if (!is_string($value) || strlen($value) > $limit) fail('Text je prázdný nebo příliš dlouhý.');
    return trim($value);
}
if ($_SERVER['REQUEST_METHOD'] !== 'POST') fail('Použijte POST.', 405);
if (isset($_SERVER['HTTP_SEC_FETCH_SITE']) && $_SERVER['HTTP_SEC_FETCH_SITE'] === 'cross-site') fail('Nepovolený původ požadavku.', 403);
if (isset($_SERVER['HTTP_ORIGIN'])) {
    $origin = parse_url($_SERVER['HTTP_ORIGIN'], PHP_URL_HOST);
    $host = explode(':', $_SERVER['HTTP_HOST'] ?? '')[0];
    if (strtolower((string)$origin) !== strtolower($host)) fail('Nepovolený původ požadavku.', 403);
}
if ((int)($_SERVER['CONTENT_LENGTH'] ?? 0) > 12 * 1024 * 1024) fail('Soubor je příliš velký.', 413);
$isUpload = str_starts_with($_SERVER['CONTENT_TYPE'] ?? '', 'multipart/form-data');
if ($isUpload) $input = $_POST;
else {
    $raw = file_get_contents('php://input', false, null, 0, 2 * 1024 * 1024 + 1);
    if (strlen($raw) > 2 * 1024 * 1024) fail('Požadavek je příliš velký.', 413);
    $input = json_decode($raw, true);
    if (!is_array($input)) fail('Neplatný požadavek.');
}
$action = $input['action'] ?? '';
$base = getenv('ALTIVOX_TOOLS_DATA') ?: dirname(__DIR__, 2) . '/.altivox-nastroje';
if (!is_dir($base) && !@mkdir($base, 0700, true)) fail('Úložiště nelze vytvořit. Zkontrolujte oprávnění hostingu.', 503);
if (!is_writable($base)) fail('Úložiště není zapisovatelné.', 503);
if (!file_exists($base . '/.htaccess')) file_put_contents($base . '/.htaccess', "Require all denied\n");
// Global guard serializes room creation / cleanup and imposes a storage bound.
if ($action === 'create') {
    $guard = fopen($base . '/guard.lock', 'c+');
    if (!$guard || !flock($guard, LOCK_EX)) fail('Úložiště je zaneprázdněné.', 503);
    $paths = glob($base . '/room-*.json') ?: [];
    foreach ($paths as $path) {
        $old = @fopen($path, 'r+');
        if (!$old || !flock($old, LOCK_EX | LOCK_NB)) { if ($old) fclose($old); continue; }
        $state = json_decode(stream_get_contents($old), true);
        if (is_array($state) && ($state['expires'] ?? 0) < time()) {
            foreach ($state['files'] ?? [] as $file) @unlink($base . '/' . $file['stored']);
            @unlink($path);
        }
        fclose($old);
    }
    if (count(glob($base . '/room-*.json') ?: []) >= 150) fail('Prostor je nyní plný. Zkuste to později.', 429);
    $ratePath = $base . '/rate.json';
    $rates = json_decode(@file_get_contents($ratePath) ?: '{}', true) ?: [];
    foreach ($rates as $ip => $rate) if ($rate['until'] < time()) unset($rates[$ip]);
    $ip = hash('sha256', ($_SERVER['REMOTE_ADDR'] ?? 'local') . __DIR__);
    $rate = $rates[$ip] ?? ['count' => 0, 'until' => time() + 3600];
    if ($rate['count'] >= 25) fail('Limit je 25 nových místností za hodinu.', 429);
    $rate['count']++; $rates[$ip] = $rate;
    file_put_contents($ratePath, json_encode($rates), LOCK_EX);
    $type = $input['type'] ?? '';
    if (!in_array($type, ['poll', 'ideas', 'files', 'collab'], true)) fail('Neznámý typ nástroje.');
    $title = strval_limit($input['title'] ?? '', 400);
    if ($title === '') fail('Doplňte název aktivity.');
    $options = [];
    if ($type === 'poll') {
        if (!is_array($input['options'] ?? null) || count($input['options']) < 2 || count($input['options']) > 10) fail('Hlasování vyžaduje 2 až 10 možností.');
        foreach ($input['options'] as $value) {
            $option = strval_limit($value, 160);
            if ($option === '' || in_array($option, $options, true)) fail('Možnosti nesmějí být prázdné ani stejné.');
            $options[] = $option;
        }
    }
    $id = bin2hex(random_bytes(8)); $key = bin2hex(random_bytes(16)); $owner = bin2hex(random_bytes(24));
    $room = ['id' => $id, 'key' => $key, 'owner' => $owner, 'type' => $type, 'title' => $title,
        'created' => time(), 'expires' => time() + 86400, 'closed' => false, 'revision' => 0,
        'options' => $options, 'votes' => [], 'ideas' => [], 'files' => [], 'strokes' => [], 'doc' => '', 'docVersion' => 0];
    file_put_contents($base . '/room-' . $id . '.json', json_encode($room, JSON_UNESCAPED_UNICODE), LOCK_EX);
    fclose($guard);
    respond(['id' => $id, 'key' => $key, 'owner' => $owner]);
}
$id = $input['id'] ?? '';
if (!is_string($id) || !preg_match('/^[a-f0-9]{16}$/D', $id)) fail('Neplatná místnost.', 404);
$path = $base . '/room-' . $id . '.json';
$handle = @fopen($path, 'r+');
if (!$handle || !flock($handle, LOCK_EX)) fail('Místnost neexistuje nebo již vypršela.', 404);
$room = json_decode(stream_get_contents($handle), true);
if (!is_array($room) || $room['expires'] < time()) { fclose($handle); fail('Platnost místnosti vypršela.', 410); }
$key = $input['key'] ?? '';
if (!is_string($key) || !hash_equals($room['key'], $key)) fail('Neplatný odkaz místnosti.', 403);
$owner = is_string($input['owner'] ?? null) && hash_equals($room['owner'], $input['owner']);
if ($action === 'read') {
    $public = $room; unset($public['owner'], $public['key'], $public['votes']);
    $public['counts'] = array_fill(0, count($room['options']), 0);
    foreach ($room['votes'] as $vote) $public['counts'][$vote]++;
    $public['total'] = count($room['votes']);
    foreach ($public['ideas'] as &$idea) { $idea['likes'] = count($idea['voters']); unset($idea['voters']); } unset($idea);
    foreach ($public['files'] as &$file) unset($file['stored']); unset($file);
    fclose($handle); respond($public);
}
if ($action === 'download') {
    $fid = $input['file'] ?? '';
    foreach ($room['files'] as $file) if ($file['id'] === $fid) {
        $fp = @fopen($base . '/' . $file['stored'], 'rb');
        if (!$fp) fail('Soubor již není dostupný.', 404);
        fclose($handle);
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: attachment; filename="download"; filename*=UTF-8\'\'' . rawurlencode($file['name']));
        header('Content-Length: ' . $file['size']); fpassthru($fp); fclose($fp); exit;
    }
    fail('Soubor nenalezen.', 404);
}
if ($action === 'close') {
    if (!$owner) fail('Pouze správce může ukončit aktivitu.', 403);
    $room['closed'] = !$room['closed'];
} else {
    if ($room['closed']) fail('Aktivita je uzavřená.', 409);
    $client = $input['client'] ?? '';
    if (!is_string($client) || !preg_match('/^[a-f0-9]{32}$/D', $client)) fail('Neplatný identifikátor účastníka.');
    switch ($action) {
    case 'vote':
        if ($room['type'] !== 'poll') fail('Tato místnost není hlasování.');
        $vote = $input['vote'] ?? null;
        if (!is_int($vote) || !isset($room['options'][$vote])) fail('Neplatná možnost.');
        if (!isset($room['votes'][$client]) && count($room['votes']) >= 300) fail('Limit účastníků byl dosažen.', 429);
        $room['votes'][$client] = $vote; break;
    case 'idea':
        if ($room['type'] !== 'ideas') fail('Tato místnost není sběr nápadů.');
        if (count($room['ideas']) >= 150) fail('Nástěnka je plná.', 429);
        $text = strval_limit($input['text'] ?? '', 1200);
        if (!$text) fail('Napište nápad.');
        $room['ideas'][] = ['id' => bin2hex(random_bytes(6)), 'text' => $text, 'voters' => []]; break;
    case 'like':
        if ($room['type'] !== 'ideas') fail('Neplatný nástroj.');
        foreach ($room['ideas'] as &$idea) if ($idea['id'] === ($input['idea'] ?? '')) {
            if (isset($idea['voters'][$client])) unset($idea['voters'][$client]);
            else { if (count($idea['voters']) >= 300) fail('Limit hlasů byl dosažen.', 429); $idea['voters'][$client] = true; }
        } unset($idea); break;
    case 'removeIdea':
        if (!$owner) fail('Pouze správce může odebrat nápad.', 403);
        $room['ideas'] = array_values(array_filter($room['ideas'], fn($i) => $i['id'] !== ($input['idea'] ?? ''))); break;
    case 'upload':
        if ($room['type'] !== 'files' || !$isUpload) fail('Neplatný přenos souboru.');
        if (count($room['files']) >= 15) fail('V místnosti může být nejvýše 15 souborů.', 429);
        $file = $_FILES['file'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK || !is_uploaded_file($file['tmp_name'])) fail('Soubor se nepodařilo nahrát. Limit hostingu může být nižší než 10 MB.');
        if ($file['size'] > 10 * 1024 * 1024 || $file['size'] < 1) fail('Limit souboru je 10 MB.');
        $name = strval_limit($file['name'], 240);
        $name = preg_replace('/[\x00-\x1f\x7f\/\\\\]/', '_', $name);
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if (!in_array($ext, ['pdf','txt','csv','docx','xlsx','pptx','odt','ods','odp','png','jpg','jpeg','webp','gif','zip','mp3','mp4'], true)) fail('Tento typ souboru není podporován.');
        if (array_sum(array_column($room['files'], 'size')) + $file['size'] > 30 * 1024 * 1024) fail('Celkový limit místnosti je 30 MB.');
        $fid = bin2hex(random_bytes(12)); $stored = $id . '-' . $fid . '.bin';
        if (!move_uploaded_file($file['tmp_name'], $base . '/' . $stored)) fail('Uložení souboru selhalo.', 503);
        $room['files'][] = ['id' => $fid, 'name' => $name, 'size' => $file['size'], 'stored' => $stored]; break;
    case 'removeFile':
        if (!$owner) fail('Pouze správce může odebrat soubor.', 403);
        foreach ($room['files'] as $file) if ($file['id'] === ($input['file'] ?? '')) @unlink($base . '/' . $file['stored']);
        $room['files'] = array_values(array_filter($room['files'], fn($f) => $f['id'] !== ($input['file'] ?? ''))); break;
    case 'stroke':
        if ($room['type'] !== 'collab' || count($room['strokes']) >= 1200) fail('Tabule je plná nebo není dostupná.');
        $stroke = $input['stroke'] ?? [];
        if (!is_array($stroke['points'] ?? null) || count($stroke['points']) < 1 || count($stroke['points']) > 1500) fail('Neplatný tah.');
        foreach ($stroke['points'] as $point) if (!is_array($point) || count($point) !== 2 || !is_numeric($point[0]) || !is_numeric($point[1]) || $point[0] < 0 || $point[0] > 1600 || $point[1] < 0 || $point[1] > 1000) fail('Neplatné souřadnice.');
        if (!preg_match('/^#[a-fA-F0-9]{6}$/D', $stroke['color'] ?? '')) fail('Neplatná barva.');
        $width = $stroke['width'] ?? 3;
        if (!is_numeric($width) || $width < 1 || $width > 50) fail('Neplatná šířka.');
        $text = isset($stroke['text']) ? strval_limit($stroke['text'], 600) : '';
        $room['strokes'][] = ['points' => $stroke['points'], 'color' => $stroke['color'], 'width' => $width, 'client' => $client, 'text' => $text]; break;
    case 'undo':
        if ($room['type'] !== 'collab') fail('Neplatný nástroj.');
        for ($i = count($room['strokes']) - 1; $i >= 0; $i--) if ($room['strokes'][$i]['client'] === $client) { array_splice($room['strokes'], $i, 1); break; } break;
    case 'clear':
        if (!$owner || $room['type'] !== 'collab') fail('Pouze správce může vymazat tabuli.', 403);
        $room['strokes'] = []; break;
    case 'doc':
        if ($room['type'] !== 'collab') fail('Neplatný nástroj.');
        if (($input['version'] ?? -1) !== $room['docVersion']) fail('Dokument mezitím změnil někdo jiný. Načtěte aktuální verzi nebo zkopírujte svůj text.', 409);
        $room['doc'] = strval_limit($input['text'] ?? '', 24000); $room['docVersion']++; break;
    default: fail('Neznámá akce.');
    }
}
$room['revision']++;
$json = json_encode($room, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
if (strlen($json) > 8 * 1024 * 1024) fail('Tabule dosáhla limitu velikosti.', 413);
rewind($handle); ftruncate($handle, 0); fwrite($handle, $json); fflush($handle); fclose($handle);
respond(['ok' => true, 'revision' => $room['revision']]);
