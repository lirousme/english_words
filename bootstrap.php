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

function appBasePath(): string
{
    $configured = trim(env('APP_BASE_PATH', ''), '/');
    if ($configured !== '') return '/' . $configured;
    $script = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
    return $script === '/' ? '' : rtrim($script, '/');
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
    if (currentUser()) { header('Location: ' . appUrl('dashboard')); exit; }
}

function requireAuth(): void
{
    if (!currentUser()) { header('Location: ' . appUrl('login')); exit; }
}

function pageHeader(string $title): void
{
    $safeTitle = htmlspecialchars($title, ENT_QUOTES, 'UTF-8');
    echo '<!doctype html><html lang="pt-BR"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>' . $safeTitle . ' · Subdrill</title><link rel="stylesheet" href="' . htmlspecialchars(appUrl('assets/css/app.css'), ENT_QUOTES) . '"></head><body>';
}

function pageFooter(): void { echo '</body></html>'; }

/**
 * Starts the authenticated application shell.
 *
 * Use this pair on every authenticated page so navigation remains consistent
 * as new areas of the application are added.
 */
function appShellHeader(string $title, string $activePage = 'dashboard'): void
{
    $user = currentUser() ?? [];
    $displayName = $user['username'] ?? 'Conta';
    $initial = strtoupper(substr(trim($displayName), 0, 1) ?: 'S');
    $dashboardUrl = htmlspecialchars(appUrl('dashboard'), ENT_QUOTES, 'UTF-8');
    $logoutUrl = htmlspecialchars(appUrl('logout'), ENT_QUOTES, 'UTF-8');
    $safeName = htmlspecialchars($displayName, ENT_QUOTES, 'UTF-8');
    $isDashboard = $activePage === 'dashboard';

    pageHeader($title);
    echo '<a class="skip-link" href="#app-content">Pular para o conteúdo</a>';
    echo '<div class="app-shell">';
    echo '<aside class="app-sidebar" aria-label="Navegação principal">';
    echo '<a class="app-brand" href="' . $dashboardUrl . '"><span aria-hidden="true">◈</span> Subdrill</a>';
    echo '<nav class="app-nav" aria-label="Áreas do aplicativo">';
    echo '<a class="app-nav-link' . ($isDashboard ? ' is-active' : '') . '" href="' . $dashboardUrl . '"' . ($isDashboard ? ' aria-current="page"' : '') . '><span aria-hidden="true">⌂</span> Visão geral</a>';
    echo '</nav>';
    echo '<div class="sidebar-account"><span class="account-avatar" aria-hidden="true">' . htmlspecialchars($initial, ENT_QUOTES, 'UTF-8') . '</span><span class="account-name">' . $safeName . '</span><a href="' . $logoutUrl . '">Sair</a></div>';
    echo '</aside><div class="app-main"><header class="app-topbar"><a class="app-brand app-brand-mobile" href="' . $dashboardUrl . '"><span aria-hidden="true">◈</span> Subdrill</a><span>' . $safeName . '</span></header><main class="app-content" id="app-content">';
}

/** Closes markup opened by appShellHeader(). */
function appShellFooter(): void
{
    echo '</main></div></div>';
    pageFooter();
}
