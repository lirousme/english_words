<?php
declare(strict_types=1);

/** Loads local configuration without requiring Composer. */
function env(string $key, string $default = ''): string
{
    static $values = null;
    if ($values === null) {
        $values = [];
        $file = __DIR__ . '/.env';
        if (is_readable($file)) {
            foreach (file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
                if (str_starts_with(ltrim($line), '#') || !str_contains($line, '=')) continue;
                [$name, $value] = explode('=', $line, 2);
                $values[trim($name)] = trim($value, " \t\n\r\0\x0B\"");
            }
        }
    }
    return $_ENV[$key] ?? getenv($key) ?: ($values[$key] ?? $default);
}

/** Kept as an alias for configuration declarations. */
function envValue(string $key, string $default = ''): string
{
    return env($key, $default);
}

define('GEMINI_API_KEY', envValue('GEMINI_API_KEY', ''));
define('GEMINI_API_URL', envValue('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'));
define('GEMINI_TRANSLATION_MODEL', envValue('GEMINI_TRANSLATION_MODEL', 'gemini-3.5-flash-lite'));
define('GOOGLE_CLOUD_API_KEY', envValue('GOOGLE_CLOUD_API_KEY', ''));

function appBasePath(): string
{
    $configured = trim(env('APP_BASE_PATH', ''), '/');
    $scriptName = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '/index.php');
    $script = str_replace('\\', '/', dirname($scriptName));
    $detected = $script === '/' ? '' : rtrim($script, '/');

    // Authentication endpoints are executed directly, so SCRIPT_NAME includes
    // /api/auth/login.php (or another nested file). Derive the public root from
    // the path of the executed file relative to this application instead of
    // redirecting users to an API subdirectory rather than an application route.
    $appDirectory = realpath(__DIR__);
    $scriptFile = realpath($_SERVER['SCRIPT_FILENAME'] ?? '');
    if ($appDirectory !== false && $scriptFile !== false) {
        $appDirectory = str_replace('\\', '/', $appDirectory);
        $scriptFile = str_replace('\\', '/', $scriptFile);
        $directoryPrefix = rtrim($appDirectory, '/') . '/';

        if (str_starts_with($scriptFile, $directoryPrefix)) {
            $relativeScript = '/' . ltrim(substr($scriptFile, strlen($directoryPrefix)), '/');
            if (str_ends_with($scriptName, $relativeScript)) {
                $detected = rtrim(substr($scriptName, 0, -strlen($relativeScript)), '/');
            }
        }
    }

    if ($configured === '') return $detected;

    $configured = '/' . $configured;
    $requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';

    // Keep an explicitly configured public path for reverse proxies, but do
    // not let an old APP_BASE_PATH make every generated link point to a
    // directory that is no longer hosting the application. In that case the
    // PHP script path is the reliable local deployment path.
    if ($requestPath === $configured || str_starts_with($requestPath, $configured . '/')) {
        return $configured;
    }

    return $detected;
}

function appUrl(string $path = ''): string
{
    return appBasePath() . ($path === '' ? '/' : '/' . ltrim($path, '/'));
}

function startSecureSession(): void
{
    if (session_status() === PHP_SESSION_ACTIVE) return;
    $https = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') === '443');
    session_name(env('APP_SESSION_NAME', 'subdrill_session'));
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => appBasePath() . '/',
        'secure' => $https,
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}

function csrfToken(): string
{
    startSecureSession();
    return $_SESSION['csrf'] ??= bin2hex(random_bytes(32));
}

function currentUser(): ?array
{
    startSecureSession();
    return $_SESSION['user'] ?? null;
}

function requireGuest(): void
{
    if (currentUser()) { header('Location: ' . appUrl('words')); exit; }
}

function requireAuth(): void
{
    if (!currentUser()) { header('Location: ' . appUrl('login')); exit; }
}

function pageHeader(string $title): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    $stylesheet = appUrl('assets/css/app.css') . '?v=' . (string) (filemtime(__DIR__ . '/assets/css/app.css') ?: 0);
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1,maximum-scale=1,user-scalable=no"><title>' . $safeTitle . '</title><link rel="stylesheet" href="' . htmlspecialchars($stylesheet, ENT_QUOTES) . '"></head><body><script>(function(){var preventZoom=function(event){event.preventDefault();};document.addEventListener("gesturestart",preventZoom,{passive:false});document.addEventListener("gesturechange",preventZoom,{passive:false});document.addEventListener("gestureend",preventZoom,{passive:false});document.addEventListener("touchmove",function(event){if(event.touches.length>1)preventZoom(event);},{passive:false});})();</script>';
}

function pageFooter(): void { echo '</body></html>'; }

/**
 * Starts the authenticated application shell.
 *
 * Use this pair on every authenticated page so navigation remains consistent
 * as new areas of the application are added.
 */
function appShellHeader(string $title, string $activePage = 'words'): void
{
    $user = currentUser() ?? [];
    $displayName = $user['username'] ?? 'Conta';
    $initial = strtoupper(substr(trim($displayName), 0, 1) ?: 'S');
    $playUrl = htmlspecialchars(appUrl('jogar'), ENT_QUOTES, 'UTF-8');
    $wordsUrl = htmlspecialchars(appUrl('words'), ENT_QUOTES, 'UTF-8');
    $logoutUrl = htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
    $isPlay = $activePage === 'play';
    $isWords = $activePage === 'words';

    pageHeader($title);
    echo '<a class="skip-link" href="#app-content">Pular para o conteúdo</a>';
    echo '<div class="app-shell">';
    echo '<aside class="app-sidebar" aria-label="Navegação principal">';
    echo '<a class="app-brand" href="' . $wordsUrl . '"><span aria-hidden="true">◈</span> Subdrill</a>';
    echo '<nav class="app-nav" aria-label="Áreas do aplicativo">';
    echo '<a class="app-nav-link' . ($isPlay ? ' is-active' : '') . '" href="' . $playUrl . '"' . ($isPlay ? ' aria-current="page"' : '') . '><span aria-hidden="true">▷</span> Jogar</a>';
    echo '<a class="app-nav-link' . ($isWords ? ' is-active' : '') . '" href="' . $wordsUrl . '"' . ($isWords ? ' aria-current="page"' : '') . '><span aria-hidden="true">▤</span> Words</a>';
    echo '</nav>';
    echo '<div class="sidebar-account"><span class="account-avatar" aria-hidden="true">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span><span class="account-name">' . $safeName . '</span><a href="' . $logoutUrl . '">Sair</a></div>';
    echo '</aside><div class="app-main"><header class="app-topbar"><a class="app-brand app-brand-mobile" href="' . $wordsUrl . '"><span aria-hidden="true">◈</span> Subdrill</a><span>' . $safeName . '</span></header><main class="app-content" id="app-content">';
}

/** Closes markup opened by appShellHeader(). */
function appShellFooter(): void
{
    echo '</main></div></div>';
    pageFooter();
}
