<?php
// includes/auth.php
if (session_status() === PHP_SESSION_NONE) session_start();
require_once __DIR__ . '/config.php';

function isLoggedIn(): bool { return isset($_SESSION['user_id']); }

function requireLogin(): void {
    if (!isLoggedIn()) { header('Location: '.baseUrl().'/login.php'); exit; }
}
function requireRole(string ...$roles): void {
    requireLogin();
    if (!in_array($_SESSION['user_role'], $roles, true)) {
        header('Location: '.baseUrl().'/index.php?err=akses'); exit;
    }
}

function currentUser(): array {
    return ['id'=>$_SESSION['user_id']??null,'nama'=>$_SESSION['user_nama']??'','role'=>$_SESSION['user_role']??''];
}

function login(string $username, string $password): bool {
    $s = getDB()->prepare("SELECT * FROM users WHERE username=? AND status='aktif' LIMIT 1");
    $s->execute([$username]);
    $u = $s->fetch();
    if ($u && password_verify($password, $u['password'])) {
        $_SESSION['user_id']   = $u['id'];
        $_SESSION['user_nama'] = $u['nama'];
        $_SESSION['user_role'] = $u['role'];
        return true;
    }
    return false;
}

function logout(): void { session_destroy(); header('Location: '.baseUrl().'/login.php'); exit; }

function baseUrl(): string {
    $proto  = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS']!=='off') ? 'https' : 'http';
    $host   = $_SERVER['HTTP_HOST'];
    $script = str_replace('\\','/',dirname($_SERVER['SCRIPT_NAME']));
    $base   = '';
    foreach (explode('/', trim($script,'/')) as $p) {
        if (!$p) continue;
        $base .= '/'.$p;
        if (file_exists($_SERVER['DOCUMENT_ROOT'].$base.'/includes/config.php')) break;
    }
    return $proto.'://'.$host.$base;
}

function flash(string $key, string $msg=null): ?string {
    if ($msg!==null) { $_SESSION['flash'][$key]=$msg; return null; }
    $v = $_SESSION['flash'][$key]??null; unset($_SESSION['flash'][$key]); return $v;
}

function e(mixed $s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }
