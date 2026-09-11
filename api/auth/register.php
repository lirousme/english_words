<?php
declare(strict_types=1);

require_once dirname(__DIR__, 2) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit('Método não permitido');
}

startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) {
    http_response_code(419);
    exit('Solicitação expirada.');
}

$username = trim((string) ($_POST['username'] ?? ''));
$password = (string) ($_POST['password'] ?? '');
$_SESSION['register_old'] = ['username' => $username];

if (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username) || strlen($password) < 8) {
    $_SESSION['auth_error'] = 'Use um usuário de 3 a 50 caracteres (letras, números, ponto, hífen ou sublinhado) e uma senha de pelo menos 8 caracteres.';
    header('Location: ' . appUrl('criar-conta'));
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4',
        env('DB_USER'),
        env('DB_PASS'),
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]
    );
    $statement = $pdo->prepare('INSERT INTO users (username, password_hash) VALUES (:username, :password_hash)');
    $statement->execute([
        'username' => $username,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    $userId = (int) $pdo->lastInsertId();
} catch (PDOException $exception) {
    if ($exception->getCode() === '23000') {
        $_SESSION['auth_error'] = 'Este usuário já existe. Entre para continuar.';
    } else {
        error_log('Subdrill register database error: ' . $exception->getMessage());
        $_SESSION['auth_error'] = 'Não foi possível criar sua conta agora. Tente novamente mais tarde.';
    }
    header('Location: ' . appUrl('criar-conta'));
    exit;
}

session_regenerate_id(true);
$_SESSION['user'] = ['id' => $userId, 'username' => $username];
unset($_SESSION['csrf'], $_SESSION['register_old']);
header('Location: ' . appUrl('dashboard'));
