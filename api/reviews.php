<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
requireAuth();
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }

function playResponse(string $message, string $type = 'success', ?array $review = null): never
{
    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['message' => $message, 'type' => $type, 'review' => $review], JSON_THROW_ON_ERROR);
        exit;
    }

    $_SESSION['play_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . appUrl('jogar'));
    exit;
}

$translationId = filter_var($_POST['translation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
if (!$translationId) playResponse('Revisão inválida.', 'error');

try {
    $userId = (int) ((currentUser() ?? [])['id'] ?? 0);
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $pdo->beginTransaction();
    $review = $pdo->prepare('SELECT id, amount, next_review <= NOW() AS is_due FROM reviews WHERE id_user = :user_id AND id_translation = :translation_id FOR UPDATE');
    $review->execute(['user_id' => $userId, 'translation_id' => $translationId]);
    $existing = $review->fetch(PDO::FETCH_ASSOC) ?: null;
    $sentence = $pdo->prepare('SELECT EXISTS (SELECT 1 FROM frases WHERE id_translation = :translation_id)');
    $sentence->execute(['translation_id' => $translationId]);
    $hasSentences = (bool) $sentence->fetchColumn();
    $isDue = $existing && (bool) $existing['is_due'];
    if (!$hasSentences || ($existing && !$isDue)) { $pdo->rollBack(); playResponse('Esta tradução não está disponível para revisão.', 'error'); }

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
    $message = 'Revisão registrada. A próxima ficará disponível em ' . $amount . ' ' . ($amount === 1 ? 'dia' : 'dias') . '.';

    if (str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json')) {
        $next = $pdo->prepare('SELECT t.id, t.portugues FROM reviews r INNER JOIN translations t ON t.id = r.id_translation WHERE r.id_user = :user_id AND r.next_review <= NOW() AND EXISTS (SELECT 1 FROM frases f WHERE f.id_translation = t.id) ORDER BY r.next_review ASC, r.id ASC LIMIT 1');
        $next->execute(['user_id' => $userId]);
        $translation = $next->fetch(PDO::FETCH_ASSOC) ?: null;
        if (!$translation) {
            $next = $pdo->prepare('SELECT t.id, t.portugues FROM translations t WHERE NOT EXISTS (SELECT 1 FROM reviews r WHERE r.id_user = :user_id AND r.id_translation = t.id) AND EXISTS (SELECT 1 FROM frases f WHERE f.id_translation = t.id) ORDER BY t.id ASC LIMIT 1');
            $next->execute(['user_id' => $userId]);
            $translation = $next->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if ($translation) {
            $sentences = $pdo->prepare('SELECT frase_portugues, frase_ingles, audio_portugues, audio_en_gb FROM frases WHERE id_translation = :id ORDER BY id ASC');
            $sentences->execute(['id' => $translation['id']]);
            $translation['sentences'] = $sentences->fetchAll(PDO::FETCH_ASSOC);
        }
        playResponse($message, 'success', $translation);
    }

    playResponse($message);
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    error_log('Subdrill review database error: ' . $exception->getMessage());
    playResponse('Não foi possível registrar a revisão agora. Tente novamente mais tarde.', 'error');
}
