<?php
declare(strict_types=1);

require_once dirname(__DIR__) . '/bootstrap.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Método não permitido'); }
requireAuth();
startSecureSession();
if (!hash_equals($_SESSION['csrf'] ?? '', (string) ($_POST['csrf'] ?? ''))) { http_response_code(419); exit('Solicitação expirada.'); }

function wordsRedirect(string $message, string $type = 'success'): never
{
    $_SESSION['words_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . appUrl('words'));
    exit;
}

function normalizedWord(string $word): string
{
    return preg_replace('/\s+/u', ' ', trim($word)) ?? '';
}

function translationsRedirect(int $wordId, string $message, string $type = 'success'): never
{
    $_SESSION['words_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . appUrl('words') . '?word=' . $wordId);
    exit;
}

/** @return list<array{portugues: string, type: int}> */
function discoverTranslations(string $word): array
{
    $apiKey = env('GEMINI_API_KEY');
    if ($apiKey === '') throw new RuntimeException('A chave do Gemini não foi configurada.');
    if (!function_exists('curl_init')) throw new RuntimeException('A extensão cURL não está disponível no servidor.');

    $model = env('GEMINI_TRANSLATION_MODEL', 'gemini-3.5-flash-lite');
    $baseUrl = rtrim(env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
    $prompt = "Você é um dicionário inglês-português. Encontre todas as traduções usuais possíveis em português brasileiro da palavra ou expressão inglesa exatamente como fornecida entre <termo> e </termo>.\n\n<termo>{$word}</termo>\n\n"
        . "Regra crítica: traduza ipsis litteris somente o termo dentro das tags. Não acrescente, remova, complete ou altere palavras. Por exemplo, se o termo for 'get', não inclua sentidos de 'get off'; se for 'get off', não inclua sentidos de apenas 'get'.\n"
        . "Classifique cada tradução com type: 1 verbo/phrasal verb/locução verbal; 2 substantivo/locução substantiva; 3 conjunção/locução conjuntiva; 4 advérbio/locução adverbial; 5 adjetivo/locução adjetiva; 6 preposição/locução prepositiva.\n"
        . 'Retorne somente JSON válido, sem markdown, no formato {"translations":[{"portugues":"...","type":1}]}. Use apenas traduções em português; não explique nada e não repita itens.';
    $payload = json_encode(['contents' => [['parts' => [['text' => $prompt]]]]], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $curl = curl_init($baseUrl . '/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey));
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        error_log('Subdrill Gemini translation error: HTTP ' . $status . ' ' . $curlError);
        throw new RuntimeException('Não foi possível consultar o Gemini agora.');
    }
    $body = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $text = $body['candidates'][0]['content']['parts'][0]['text'] ?? '';
    if (!is_string($text)) throw new RuntimeException('O Gemini retornou uma resposta inválida.');
    $text = preg_replace('/^```(?:json)?\s*|\s*```$/i', '', trim($text)) ?? '';
    $result = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($result['translations'] ?? null)) throw new RuntimeException('O Gemini não retornou traduções no formato esperado.');

    $translations = [];
    foreach ($result['translations'] as $item) {
        $translation = preg_replace('/\s+/u', ' ', trim(is_array($item) ? (string) ($item['portugues'] ?? '') : '')) ?? '';
        $type = is_array($item) ? filter_var($item['type'] ?? null, FILTER_VALIDATE_INT) : false;
        if ($translation === '' || mb_strlen($translation) > 255 || !is_int($type) || $type < 1 || $type > 6) continue;
        $translations[$type . ':' . mb_strtolower($translation)] = ['portugues' => $translation, 'type' => $type];
    }
    if ($translations === []) throw new RuntimeException('O Gemini não encontrou traduções válidas para esta palavra.');
    return array_values($translations);
}

$action = (string) ($_POST['action'] ?? '');
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$word = normalizedWord((string) ($_POST['word'] ?? ''));

if (in_array($action, ['create', 'update'], true) && ($word === '' || mb_strlen($word) > 150 || preg_match('/[\x00-\x1F\x7F]/u', $word))) {
    wordsRedirect('Informe uma palavra válida de até 150 caracteres.', 'error');
}
if (in_array($action, ['update', 'delete'], true) && !$id) wordsRedirect('Palavra inválida.', 'error');
if ($action === 'discover' && !$id) wordsRedirect('Palavra inválida.', 'error');

try {
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    if ($action === 'create') {
        $pdo->prepare('INSERT INTO words (word) VALUES (:word)')->execute(['word' => $word]);
        wordsRedirect('Palavra adicionada com sucesso.');
    }
    if ($action === 'update') {
        $statement = $pdo->prepare('UPDATE words SET word = :word WHERE id = :id');
        $statement->execute(['word' => $word, 'id' => $id]);
        if ($statement->rowCount()) wordsRedirect('Palavra atualizada com sucesso.');
        $exists = $pdo->prepare('SELECT 1 FROM words WHERE id = :id');
        $exists->execute(['id' => $id]);
        $wordExists = (bool) $exists->fetchColumn();
        wordsRedirect($wordExists ? 'Palavra atualizada com sucesso.' : 'Palavra não encontrada.', $wordExists ? 'success' : 'error');
    }
    if ($action === 'delete') {
        $statement = $pdo->prepare('DELETE FROM words WHERE id = :id');
        $statement->execute(['id' => $id]);
        wordsRedirect($statement->rowCount() ? 'Palavra removida com sucesso.' : 'Palavra não encontrada.', $statement->rowCount() ? 'success' : 'error');
    }
    if ($action === 'discover') {
        $wordStatement = $pdo->prepare('SELECT word FROM words WHERE id = :id');
        $wordStatement->execute(['id' => $id]);
        $storedWord = $wordStatement->fetchColumn();
        if (!is_string($storedWord)) translationsRedirect((int) $id, 'Palavra não encontrada.', 'error');
        $translations = discoverTranslations($storedWord);
        $pdo->beginTransaction();
        $pdo->prepare('DELETE FROM translations WHERE id_word = :id')->execute(['id' => $id]);
        $insert = $pdo->prepare('INSERT INTO translations (id_word, portugues, `type`) VALUES (:id_word, :portugues, :type)');
        foreach ($translations as $translation) $insert->execute(['id_word' => $id, 'portugues' => $translation['portugues'], 'type' => $translation['type']]);
        $pdo->commit();
        translationsRedirect((int) $id, count($translations) . ' traduções encontradas e salvas.');
    }
    wordsRedirect('Ação inválida.', 'error');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($exception->getCode() === '23000') wordsRedirect('Essa palavra já está cadastrada.', 'error');
    error_log('Subdrill words database error: ' . $exception->getMessage());
    wordsRedirect('Não foi possível salvar a palavra agora. Tente novamente mais tarde.', 'error');
}
