<?php
declare(strict_types=1);
require __DIR__ . '/bootstrap.php';

$path = rtrim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/', '/');
$base = appBasePath();
if ($base && ($path === $base || str_starts_with($path, $base . '/'))) $path = substr($path, strlen($base)) ?: '/';
$path = '/' . ltrim($path, '/');

// A few shared-hosting setups expose the front controller in the URL (for
// example, /index.php or /app.index). Treat those addresses as routes instead
// of returning a 404, including when a route follows the controller filename.
foreach (['/index.php', '/app.index'] as $controller) {
    if ($path === $controller) {
        $path = '/';
        break;
    }
    if (str_starts_with($path, $controller . '/')) {
        $path = substr($path, strlen($controller)) ?: '/';
        break;
    }
}

switch ($path) {
    case '/': case '/login':
        requireGuest();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/api/auth/login.php'; }
        require __DIR__ . '/pages/login.php';
        break;
    case '/criar-conta':
        requireGuest();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/api/auth/register.php'; }
        require __DIR__ . '/pages/register.php';
        break;
    case '/dashboard':
        requireAuth();
        header('Location: ' . appUrl('words'), true, 302);
        exit;
    case '/jogar':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/api/reviews.php'; }
        require __DIR__ . '/pages/play.php';
        break;
    case '/words':
        requireAuth();
        if ($_SERVER['REQUEST_METHOD'] === 'POST') { require __DIR__ . '/api/words.php'; }
        require __DIR__ . '/pages/words.php';
        break;
    case '/logout': require __DIR__ . '/api/auth/logout.php'; break;
    default: http_response_code(404); pageHeader('Página não encontrada'); echo '<main class="center"><section class="card"><p class="eyebrow">404</p><h1>Página não encontrada</h1><a class="button" href="' . htmlspecialchars(appUrl(), ENT_QUOTES) . '">Voltar ao início</a></section></main>'; pageFooter();
}
