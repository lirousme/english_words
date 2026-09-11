<?php
declare(strict_types=1);
require dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }
$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$_SESSION['login_username'] = $username;
if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) || $password === '') { $_SESSION['auth_error'] = 'Informe seu usuário e sua senha.'; header('Location: ' . appUrl('login')); exit; }

try {
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $statement = $pdo->prepare('SELECT id, username, password_hash FROM users WHERE username = :username LIMIT 1');
    $statement->execute(['username' => $username]);
    $user = $statement->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $exception) { error_log('Subdrill login database error: ' . $exception->getMessage()); $_SESSION['auth_error'] = 'Não foi possível entrar agora. Tente novamente mais tarde.'; header('Location: ' . appUrl('login')); exit; }

if (!$user || !password_verify($password, $user['password_hash'])) { usleep(250000); $_SESSION['auth_error'] = 'Usuário ou senha incorretos.'; header('Location: ' . appUrl('login')); exit; }
session_regenerate_id(true);
$_SESSION['user'] = ['id' => (int) $user['id'], 'username' => $user['username']];
unset($_SESSION['csrf'], $_SESSION['login_username']);
header('Location: ' . appUrl('dashboard'));
