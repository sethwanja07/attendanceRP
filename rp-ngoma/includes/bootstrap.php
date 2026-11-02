<?php
require_once __DIR__ . '/../config.php';
ini_set('display_errors', DEBUG ? '1' : '0');
session_set_cookie_params(['httponly' => true, 'samesite' => 'Lax', 'secure' => !empty($_SERVER['HTTPS'])]);
session_start();

/** Web path of the project folder, e.g. "/" on a domain root or "/rp-ngoma/" in a subfolder. */
function base_url(): string {
    static $b;
    if ($b === null) {
        $root = str_replace('\\', '/', realpath(__DIR__ . '/..'));
        $doc  = str_replace('\\', '/', realpath($_SERVER['DOCUMENT_ROOT']));
        $b = rtrim(substr($root, strlen($doc)), '/') . '/';
    }
    return $b;
}
/** Clean URL helper: url('trainer/') -> /trainer/  ,  url('logout') -> /logout */
function url(string $path = ''): string { return base_url() . ltrim($path, '/'); }
function redirect(string $path = ''): void { header('Location: ' . url($path)); exit; }

function db(): PDO {
    static $pdo;
    if (!$pdo) {
        try {
            $pdo = new PDO('mysql:host=' . DB_HOST . ';dbname=' . DB_NAME . ';charset=utf8mb4', DB_USER, DB_PASS,
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]);
        } catch (PDOException $ex) {
            http_response_code(500);
            exit('Cannot connect to the database. Check DB_HOST, DB_NAME, DB_USER and DB_PASS in config.php.' . (DEBUG ? ' ' . e($ex->getMessage()) : ''));
        }
    }
    return $pdo;
}
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }
function user() { return $_SESSION['user'] ?? null; }
function require_role(string $role): array {
    $u = user();
    if (!$u || $u['role'] !== $role) { redirect(''); }
    return $u;
}
function csrf(): string { return $_SESSION['csrf'] ??= bin2hex(random_bytes(16)); }
function csrf_check(): void {
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(400); exit('Invalid form token. Go back and try again.'); }
}
function flash(?string $msg = null, string $type = 'ok') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}
function page_start(string $title): void {
    $u = user(); $f = flash(); ?>
<!DOCTYPE html><html lang="en"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1">
<title><?= e($title) ?> · RP-Ngoma College</title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head><body>
<header><div><h1>RP-Ngoma College</h1><small>Attendance portal</small></div>
<?php if ($u): ?><div><?= e($u['full_name']) ?> <a class="btn ghost" href="<?= e(url('logout')) ?>">Log out</a></div><?php endif; ?></header>
<main><?php if ($f): ?><div class="msg <?= e($f[1]) ?>"><?= e($f[0]) ?></div><?php endif;
}
function page_end(): void { echo '</main></body></html>'; }
