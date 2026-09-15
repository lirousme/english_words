<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
requireAuth();
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }

function playRedirect(string $message, string $type = 'success'): never
{
    $_SESSION['play_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . appUrl('jogar'));
    exit;
}

$translationId = filter_var($_POST['translation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$translationId) playRedirect('Revisão inválida.', 'error');

try {
    $userId = (int) ((currentUser() ?? [])['id'] ?? 0);
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->beginTransaction();
    $review = $pdo->prepare('SELECT id, amount FROM reviews WHERE id_user = :user_id AND id_translation = :translation_id FOR UPDATE');
    $review->execute(['user_id' => $userId, 'translation_id' => $translationId]);
    $existing = $review->fetch(PDO::FETCH_ASSOC) ?: null;
    $eligible = $pdo->prepare('SELECT EXISTS (SELECT 1 FROM frases WHERE id_translation = :translation_id) AND (EXISTS (SELECT 1 FROM reviews WHERE id_user = :user_id AND id_translation = :translation_id AND next_review <= NOW()) OR NOT EXISTS (SELECT 1 FROM reviews WHERE id_user = :user_id AND id_translation = :translation_id))');
    $eligible->execute(['user_id' => $userId, 'translation_id' => $translationId]);
    if (!(bool) $eligible->fetchColumn()) { $pdo->rollBack(); playRedirect('Esta tradução não está disponível para revisão.', 'error'); }

    $amount = $existing ? (int) $existing['amount'] + 1 : 1;
    $nextReview = (new DateTimeImmutable('now'))->modify('+' . $amount . ' days')->format('Y-m-d H:i:s');
    if ($existing) {
        $update = $pdo->prepare('UPDATE reviews SET amount = :amount, next_review = :next_review WHERE id = :id');
        $update->execute(['amount' => $amount, 'next_review' => $nextReview, 'id' => $existing['id']]);
    } else {
        $insert = $pdo->prepare('INSERT INTO reviews (id_user, id_translation, amount, next_review) VALUES (:user_id, :translation_id, :amount, :next_review)');
        $insert->execute(['user_id' => $userId, 'translation_id' => $translationId, 'amount' => $amount, 'next_review' => $nextReview]);
    }
    $pdo->commit();
    playRedirect('Revisão registrada. A próxima ficará disponível em ' . $amount . ' ' . ($amount === 1 ? 'dia' : 'dias') . '.');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Subdrill review database error: ' . $exception->getMessage());
    playRedirect('Não foi possível registrar a revisão agora. Tente novamente mais tarde.', 'error');
}
