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

function normalizeEnglishContractions(string $sentence): string
{
    $sentence = preg_replace('/\s+/u', ' ', trim($sentence)) ?? '';

    $replacements = [
        // I
        '/\bI am\b/u' => "I'm",
        '/\bI have\b/u' => "I've",
        '/\bI had\b/u' => "I'd",
        '/\bI will\b/u' => "I'll",
        '/\bI would\b/u' => "I'd",
        '/\bI shall\b/u' => "I'll",

        // You
        '/\bYou are\b/u' => "You're",
        '/\bYou have\b/u' => "You've",
        '/\bYou had\b/u' => "You'd",
        '/\bYou will\b/u' => "You'll",
        '/\bYou would\b/u' => "You'd",

        // He
        '/\bHe is\b/u' => "He's",
        '/\bHe has\b/u' => "He's",
        '/\bHe had\b/u' => "He'd",
        '/\bHe will\b/u' => "He'll",
        '/\bHe would\b/u' => "He'd",

        // She
        '/\bShe is\b/u' => "She's",
        '/\bShe has\b/u' => "She's",
        '/\bShe had\b/u' => "She'd",
        '/\bShe will\b/u' => "She'll",
        '/\bShe would\b/u' => "She'd",

        // It
        '/\bIt is\b/u' => "It's",
        '/\bIt has\b/u' => "It's",
        '/\bIt had\b/u' => "It'd",
        '/\bIt will\b/u' => "It'll",
        '/\bIt would\b/u' => "It'd",

        // We
        '/\bWe are\b/u' => "We're",
        '/\bWe have\b/u' => "We've",
        '/\bWe had\b/u' => "We'd",
        '/\bWe will\b/u' => "We'll",
        '/\bWe would\b/u' => "We'd",

        // They
        '/\bThey are\b/u' => "They're",
        '/\bThey have\b/u' => "They've",
        '/\bThey had\b/u' => "They'd",
        '/\bThey will\b/u' => "They'll",
        '/\bThey would\b/u' => "They'd",

        // There
        '/\bThere is\b/u' => "There's",
        '/\bThere has\b/u' => "There's",
        '/\bThere are\b/u' => "There're",
        '/\bThere have\b/u' => "There've",
        '/\bThere had\b/u' => "There'd",
        '/\bThere will\b/u' => "There'll",
        '/\bThere would\b/u' => "There'd",

        // That
        '/\bThat is\b/u' => "That's",
        '/\bThat has\b/u' => "That's",
        '/\bThat had\b/u' => "That'd",
        '/\bThat will\b/u' => "That'll",
        '/\bThat would\b/u' => "That'd",

        // Who
        '/\bWho is\b/u' => "Who's",
        '/\bWho has\b/u' => "Who's",
        '/\bWho had\b/u' => "Who'd",
        '/\bWho will\b/u' => "Who'll",
        '/\bWho would\b/u' => "Who'd",

        // What
        '/\bWhat is\b/u' => "What's",
        '/\bWhat has\b/u' => "What's",
        '/\bWhat had\b/u' => "What'd",
        '/\bWhat will\b/u' => "What'll",
        '/\bWhat would\b/u' => "What'd",

        // Where
        '/\bWhere is\b/u' => "Where's",
        '/\bWhere has\b/u' => "Where's",
        '/\bWhere had\b/u' => "Where'd",
        '/\bWhere will\b/u' => "Where'll",
        '/\bWhere would\b/u' => "Where'd",

        // When
        '/\bWhen is\b/u' => "When's",
        '/\bWhen has\b/u' => "When's",
        '/\bWhen had\b/u' => "When'd",
        '/\bWhen will\b/u' => "When'll",
        '/\bWhen would\b/u' => "When'd",

        // Why
        '/\bWhy is\b/u' => "Why's",
        '/\bWhy has\b/u' => "Why's",
        '/\bWhy had\b/u' => "Why'd",
        '/\bWhy will\b/u' => "Why'll",
        '/\bWhy would\b/u' => "Why'd",

        // How
        '/\bHow is\b/u' => "How's",
        '/\bHow has\b/u' => "How's",
        '/\bHow had\b/u' => "How'd",
        '/\bHow will\b/u' => "How'll",
        '/\bHow would\b/u' => "How'd",

        // Modals + have
        '/\bcould have\b/u' => "could've",
        '/\bshould have\b/u' => "should've",
        '/\bwould have\b/u' => "would've",
        '/\bmight have\b/u' => "might've",
        '/\bmust have\b/u' => "must've",
        '/\bmay have\b/u' => "may've",
        '/\bcan have\b/u' => "can've",

        // Negatives
        '/\bdo not\b/u' => "don't",
        '/\bdoes not\b/u' => "doesn't",
        '/\bdid not\b/u' => "didn't",
        '/\bis not\b/u' => "isn't",
        '/\bare not\b/u' => "aren't",
        '/\bam not\b/u' => "I'm not",
        '/\bwas not\b/u' => "wasn't",
        '/\bwere not\b/u' => "weren't",
        '/\bhas not\b/u' => "hasn't",
        '/\bhave not\b/u' => "haven't",
        '/\bhad not\b/u' => "hadn't",
        '/\bwill not\b/u' => "won't",
        '/\bwould not\b/u' => "wouldn't",
        '/\bcould not\b/u' => "couldn't",
        '/\bshould not\b/u' => "shouldn't",
        '/\bmight not\b/u' => "mightn't",
        '/\bmust not\b/u' => "mustn't",
        '/\bneed not\b/u' => "needn't",
        '/\bdare not\b/u' => "daren't",
        '/\bused to not\b/u' => "used not to",

        // Cannot / can not
        '/\bcannot\b/u' => "can't",
        '/\bcan not\b/u' => "can't",
    ];

    foreach ($replacements as $pattern => $replacement) {
        $sentence = preg_replace($pattern, $replacement, $sentence) ?? $sentence;
    }

    return $sentence;
}

function translationsRedirect(int $wordId, string $message, string $type = 'success'): never
{
    $_SESSION['words_flash'] = ['message' => $message, 'type' => $type];
    header('Location: ' . appUrl('words') . '?word=' . $wordId);
    exit;
}

function englishSentenceUsesExactTerm(string $sentence, string $term): bool
{
    $pattern = "~(?<![\\p{L}\\p{N}'’\\-])" . preg_quote($term, '~') . "(?![\\p{L}\\p{N}'’\\-])~iu";
    return preg_match($pattern, $sentence) === 1;
}

function synthesizeSentenceAudio(string $text, string $languageCode, string $voiceName): string
{
    if (GOOGLE_CLOUD_API_KEY === '') throw new RuntimeException('A chave do Google Cloud não foi configurada.');
    if (!function_exists('curl_init')) throw new RuntimeException('A extensão cURL não está disponível no servidor.');

    $payload = json_encode([
        'input' => ['text' => $text],
        'voice' => ['languageCode' => $languageCode, 'name' => $voiceName],
        'audioConfig' => ['audioEncoding' => 'MP3'],
    ], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $curl = curl_init('https://texttospeech.googleapis.com/v1/text:synthesize?key=' . rawurlencode(GOOGLE_CLOUD_API_KEY));
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $payload, CURLOPT_HTTPHEADER => ['Content-Type: application/json'], CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        error_log('Subdrill Google Cloud TTS error: HTTP ' . $status . ' ' . $curlError);
        throw new RuntimeException('Não foi possível gerar os áudios agora.');
    }
    $body = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $audio = $body['audioContent'] ?? '';
    if (!is_string($audio) || $audio === '' || base64_decode($audio, true) === false) throw new RuntimeException('O Google Cloud retornou um áudio inválido.');
    return $audio;
}

function selectedAiProvider(): string
{
    $provider = strtolower(trim(env('AI_PROVIDER', 'gemini')));
    if (!in_array($provider, ['gemini', 'openrouter'], true)) {
        throw new RuntimeException('O provedor de IA configurado é inválido. Use gemini ou openrouter.');
    }
    return $provider;
}

/** Sends a prompt to the selected AI provider and returns its JSON response text. */
function generateAiJson(string $prompt): string
{
    if (!function_exists('curl_init')) throw new RuntimeException('A extensão cURL não está disponível no servidor.');

    $provider = selectedAiProvider();
    if ($provider === 'gemini') {
        $apiKey = env('GEMINI_API_KEY');
        if ($apiKey === '') throw new RuntimeException('A chave do Gemini não foi configurada.');

        $model = env('GEMINI_TRANSLATION_MODEL', 'gemini-3.5-flash-lite');
        $baseUrl = rtrim(env('GEMINI_API_URL', 'https://generativelanguage.googleapis.com/v1beta/models'), '/');
        $url = $baseUrl . '/' . rawurlencode($model) . ':generateContent?key=' . rawurlencode($apiKey);
        $payload = [
            'contents' => [['parts' => [['text' => $prompt]]]],
            'generationConfig' => ['responseMimeType' => 'application/json'],
        ];
        $headers = ['Content-Type: application/json'];
    } else {
        $apiKey = env('OPENROUTER_API_KEY', env('API_KEY'));
        $model = env('OPENROUTER_TRANSLATION_MODEL');
        if ($apiKey === '') throw new RuntimeException('A chave do OpenRouter não foi configurada.');
        if ($model === '') throw new RuntimeException('O modelo do OpenRouter não foi configurado.');

        $url = env('OPENROUTER_API_URL', 'https://openrouter.ai/api/v1/chat/completions');
        $payload = [
            'model' => $model,
            'messages' => [['role' => 'user', 'content' => $prompt]],
        ];
        $headers = ['Content-Type: application/json', 'Authorization: Bearer ' . $apiKey];
    }

    $request = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $curl = curl_init($url);
    curl_setopt_array($curl, [CURLOPT_POST => true, CURLOPT_POSTFIELDS => $request, CURLOPT_HTTPHEADER => $headers, CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 45]);
    $response = curl_exec($curl);
    $status = (int) curl_getinfo($curl, CURLINFO_RESPONSE_CODE);
    $curlError = curl_error($curl);
    curl_close($curl);
    if (!is_string($response) || $status < 200 || $status >= 300) {
        error_log('Subdrill ' . $provider . ' text generation error: HTTP ' . $status . ' ' . $curlError);
        throw new RuntimeException('Não foi possível consultar ' . ($provider === 'gemini' ? 'o Gemini' : 'o OpenRouter') . ' agora.');
    }

    $body = json_decode($response, true, 512, JSON_THROW_ON_ERROR);
    $text = $provider === 'gemini'
        ? ($body['candidates'][0]['content']['parts'][0]['text'] ?? '')
        : ($body['choices'][0]['message']['content'] ?? '');
    if (!is_string($text) || trim($text) === '') {
        throw new RuntimeException(($provider === 'gemini' ? 'O Gemini' : 'O OpenRouter') . ' retornou uma resposta inválida.');
    }

    return preg_replace('/^```(?:json)?\\s*|\\s*```$/i', '', trim($text)) ?? '';
}

/** @return array{translations: list<array{portugues: string, type: int, sentences: list<array{frase_portugues: string, frase_ingles: string}>}>, relatedExpressions: list<string>} */
function discoverTranslations(string $word): array
{
    $prompt = "Dicionário inglês → pt-BR. Termo exato: <termo>{$word}</termo>.\n"
        . "Retorne cada sentido usual do termo isolado (não de expressões maiores/menores) com portugues e type: 1=verbo/locução verbal, 2=substantivo, 3=conjunção, 4=advérbio, 5=adjetivo, 6=preposição.\n"
        . "Para CADA tradução, gere exatamente 10 sentences distintas, curtas e coloquiais. Em cada uma, frase_ingles deve conter literalmente o termo, sem flexão, substituição ou palavras extras; frase_portugues deve ser a tradução natural desse sentido, só em pt-BR e sem o termo em inglês. Prefira contrações comuns.\n"
        . "related_expressions: somente phrasal verbs ou locuções usuais formados pelo termo, diferentes dele e estudáveis separadamente; exclua flexões, sinônimos, traduções e invenções. Sem explicações ou repetições.\n"
        . 'JSON: {"translations":[{"portugues":"","type":1,"sentences":[{"frase_portugues":"","frase_ingles":""}]}],"related_expressions":[]}.';
    $text = generateAiJson($prompt);
    $result = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($result['translations'] ?? null)) throw new RuntimeException('A IA não retornou traduções no formato esperado.');

    $translations = [];
    foreach ($result['translations'] as $item) {
        $translation = preg_replace('/\s+/u', ' ', trim(is_array($item) ? (string) ($item['portugues'] ?? '') : '')) ?? '';
        $type = is_array($item) ? filter_var($item['type'] ?? null, FILTER_VALIDATE_INT) : false;
        $rawSentences = is_array($item) ? ($item['sentences'] ?? null) : null;
        if ($translation === '' || mb_strlen($translation) > 255 || !is_int($type) || $type < 1 || $type > 6 || !is_array($rawSentences)) continue;

        $sentences = [];
        $knownSentences = [];
        foreach ($rawSentences as $sentence) {
            $portugueseSentence = preg_replace('/\s+/u', ' ', trim(is_array($sentence) ? (string) ($sentence['frase_portugues'] ?? '') : '')) ?? '';
            $englishSentence = preg_replace('/\s+/u', ' ', trim(is_array($sentence) ? (string) ($sentence['frase_ingles'] ?? '') : '')) ?? '';
            if ($portugueseSentence === '' || $englishSentence === '' || mb_strlen($portugueseSentence) > 2000 || mb_strlen($englishSentence) > 2000 || !englishSentenceUsesExactTerm($englishSentence, $word)) continue;

            $englishSentence = normalizeEnglishContractions($englishSentence);
            $sentenceKey = mb_strtolower($englishSentence);
            if (isset($knownSentences[$sentenceKey])) continue;
            $knownSentences[$sentenceKey] = true;
            $sentences[] = ['frase_portugues' => $portugueseSentence, 'frase_ingles' => $englishSentence];
        }
        if (count($sentences) !== 10) continue;

        $translations[$type . ':' . mb_strtolower($translation)] = [
            'portugues' => $translation,
            'type' => $type,
            'sentences' => $sentences,
        ];
    }
    if ($translations === []) throw new RuntimeException('A IA não encontrou traduções válidas para esta palavra.');

    $relatedExpressions = [];
    $rawExpressions = $result['related_expressions'] ?? [];
    if (!is_array($rawExpressions)) $rawExpressions = [];
    foreach ($rawExpressions as $item) {
        if (!is_string($item)) continue;
        $expression = normalizedWord($item);
        if ($expression === '' || mb_strlen($expression) > 150 || preg_match('/[\x00-\x1F\x7F]/u', $expression) || !preg_match('/\s/u', $expression) || mb_strtolower($expression) === mb_strtolower($word)) continue;
        $relatedExpressions[mb_strtolower($expression)] = $expression;
    }

    return ['translations' => array_values($translations), 'relatedExpressions' => array_values($relatedExpressions)];
}

/** @param list<array{frase_portugues: string, frase_ingles: string}> $existingSentences
 *  @return list<array{frase_portugues: string, frase_ingles: string}> */
function generateAdditionalSentences(string $word, string $translation, array $existingSentences, int $quantity): array
{
    if ($quantity < 1) return [];
    $existingJson = json_encode($existingSentences, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    $prompt = "Crie exatamente {$quantity} exemplos novos, curtos, coloquiais e distintos para o sentido informado.\n"
        . "Termo exato em inglês: <termo>{$word}</termo>\nTradução em pt-BR: <traducao>{$translation}</traducao>\n\n"
        . "Cada frase em inglês deve usar o termo literalmente, sem flexioná-lo, substituí-lo ou acrescentar palavras, e deve soar nativa (prefira contrações comuns). A frase em português deve traduzir a correspondente, usar esse sentido e estar inteiramente em pt-BR, sem o termo em inglês.\n"
        . "Não repita nem reformule estes exemplos: {$existingJson}\n"
        . 'Não explique nada. JSON: {"sentences":[{"frase_portugues":"","frase_ingles":""}]}.';
    $text = generateAiJson($prompt);
    $result = json_decode($text, true, 512, JSON_THROW_ON_ERROR);
    if (!is_array($result['sentences'] ?? null)) throw new RuntimeException('A IA não retornou frases no formato esperado.');

    $known = [];
    foreach ($existingSentences as $sentence) $known[mb_strtolower($sentence['frase_ingles'])] = true;
    $sentences = [];
    foreach ($result['sentences'] as $item) {
        $portugueseSentence = preg_replace('/\s+/u', ' ', trim(is_array($item) ? (string) ($item['frase_portugues'] ?? '') : '')) ?? '';
        $englishSentence = preg_replace('/\s+/u', ' ', trim(is_array($item) ? (string) ($item['frase_ingles'] ?? '') : '')) ?? '';
        $englishSentence = normalizeEnglishContractions($englishSentence);
        $key = mb_strtolower($englishSentence);

        if ($portugueseSentence === '' || $englishSentence === '' || mb_strlen($portugueseSentence) > 2000 || mb_strlen($englishSentence) > 2000 || !englishSentenceUsesExactTerm($englishSentence, $word) || isset($known[$key])) continue;
        $known[$key] = true;
        $sentences[] = ['frase_portugues' => $portugueseSentence, 'frase_ingles' => $englishSentence];
    }
    if (count($sentences) !== $quantity) throw new RuntimeException('A IA não gerou a quantidade esperada de frases válidas.');
    return $sentences;
}

/** @return array{word: string, translation: string, english: string, portuguese: string}|null */
function csvImportRow(array $columns, int $line): ?array
{
    if ($line === 1 && isset($columns[0])) $columns[0] = preg_replace('/^\xEF\xBB\xBF/u', '', (string) $columns[0]) ?? (string) $columns[0];
    if (count($columns) !== 4) throw new RuntimeException("A linha {$line} deve ter exatamente 4 colunas.");

    [$rawWord, $rawTranslation, $rawEnglish, $rawPortuguese] = array_map(static fn($value): string => is_string($value) ? $value : '', $columns);
    $word = normalizedWord($rawWord);
    $translation = normalizedWord($rawTranslation);
    $english = preg_replace('/\s+/u', ' ', trim($rawEnglish)) ?? '';
    $portuguese = preg_replace('/\s+/u', ' ', trim($rawPortuguese)) ?? '';

    if ($word === '' && $translation === '' && $english === '' && $portuguese === '') return null;
    if ($word === '' || mb_strlen($word) > 150 || preg_match('/[\x00-\x1F\x7F]/u', $word)) throw new RuntimeException("A palavra na linha {$line} é inválida.");
    if ($translation === '' || mb_strlen($translation) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $translation)) throw new RuntimeException("A tradução na linha {$line} é inválida.");
    if ($english === '' || $portuguese === '' || mb_strlen($english) > 2000 || mb_strlen($portuguese) > 2000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $english . $portuguese)) throw new RuntimeException("As frases na linha {$line} são inválidas.");

    return ['word' => $word, 'translation' => $translation, 'english' => $english, 'portuguese' => $portuguese];
}

/** @return array{validRows: int, delimiter: string} */
function validateCsvImport(string $path): array
{
    $file = fopen($path, 'rb');
    if ($file === false) throw new RuntimeException('Não foi possível abrir a planilha enviada.');
    $firstLine = fgets($file);
    if ($firstLine === false) throw new RuntimeException('A planilha está vazia.');
    $delimiter = substr_count($firstLine, ';') > substr_count($firstLine, ',') ? ';' : ',';
    rewind($file);
    $validRows = 0;
    $line = 0;
    while (($columns = fgetcsv($file, 0, $delimiter, '"', '\\')) !== false) {
        $line++;
        if (csvImportRow($columns, $line) !== null) $validRows++;
    }
    fclose($file);
    if ($validRows === 0) throw new RuntimeException('A planilha não contém registros válidos.');
    return ['validRows' => $validRows, 'delimiter' => $delimiter];
}

function importCsvTranslations(PDO $pdo, string $path): int
{
    $validation = validateCsvImport($path);
    $file = fopen($path, 'rb');
    if ($file === false) throw new RuntimeException('Não foi possível abrir a planilha enviada.');

    $upsertWord = $pdo->prepare('INSERT INTO words (word) VALUES (:word) ON DUPLICATE KEY UPDATE id = LAST_INSERT_ID(id)');
    $findTranslation = $pdo->prepare('SELECT id FROM translations WHERE id_word = :id_word AND portugues = :portugues ORDER BY id ASC LIMIT 1');
    $insertTranslation = $pdo->prepare('INSERT INTO translations (id_word, portugues, `type`) VALUES (:id_word, :portugues, 2)');
    $insertSentence = $pdo->prepare('INSERT INTO frases (id_translation, frase_portugues, frase_ingles) VALUES (:id_translation, :frase_portugues, :frase_ingles)');
    $line = 0;
    $imported = 0;
    $inBatch = false;

    try {
        while (($columns = fgetcsv($file, 0, $validation['delimiter'], '"', '\\')) !== false) {
            $line++;
            $row = csvImportRow($columns, $line);
            if ($row === null) continue;
            if (!$inBatch) { $pdo->beginTransaction(); $inBatch = true; }

            $upsertWord->execute(['word' => $row['word']]);
            $wordId = (int) $pdo->lastInsertId();
            $findTranslation->execute(['id_word' => $wordId, 'portugues' => $row['translation']]);
            $storedTranslationId = $findTranslation->fetchColumn();
            if ($storedTranslationId === false) {
                $insertTranslation->execute(['id_word' => $wordId, 'portugues' => $row['translation']]);
                $storedTranslationId = (int) $pdo->lastInsertId();
            }
            $insertSentence->execute(['id_translation' => $storedTranslationId, 'frase_portugues' => $row['portuguese'], 'frase_ingles' => $row['english']]);
            $imported++;
            if ($imported % 1000 === 0) { $pdo->commit(); $inBatch = false; }
        }
        if ($inBatch) $pdo->commit();
    } catch (Throwable $exception) {
        if ($inBatch && $pdo->inTransaction()) $pdo->rollBack();
        throw $exception;
    } finally {
        fclose($file);
    }

    return $imported;
}

$action = (string) ($_POST['action'] ?? '');
$id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$translationId = filter_var($_POST['translation_id'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
$word = normalizedWord((string) ($_POST['word'] ?? ''));
$translation = normalizedWord((string) ($_POST['translation'] ?? ''));
$englishSentence = preg_replace('/\s+/u', ' ', trim((string) ($_POST['english_sentence'] ?? ''))) ?? '';
$portugueseSentence = preg_replace('/\s+/u', ' ', trim((string) ($_POST['portuguese_sentence'] ?? ''))) ?? '';
$translationType = filter_var($_POST['translation_type'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1, 'max_range' => 6]]);

if (in_array($action, ['create', 'update'], true) && ($word === '' || mb_strlen($word) > 150 || preg_match('/[\x00-\x1F\x7F]/u', $word))) {
    wordsRedirect('Informe uma palavra válida de até 150 caracteres.', 'error');
}
if (in_array($action, ['update', 'delete'], true) && !$id) wordsRedirect('Palavra inválida.', 'error');
if ($action === 'discover' && !$id) wordsRedirect('Palavra inválida.', 'error');
if ($action === 'generate_more' && (!$id || !$translationId)) wordsRedirect('Tradução inválida.', 'error');
if ($action === 'manual_translation' && (!$id || $translation === '' || mb_strlen($translation) > 255 || preg_match('/[\x00-\x1F\x7F]/u', $translation) || !$translationType || $englishSentence === '' || $portugueseSentence === '' || mb_strlen($englishSentence) > 2000 || mb_strlen($portugueseSentence) > 2000 || preg_match('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', $englishSentence . $portugueseSentence))) translationsRedirect((int) $id, 'Informe uma tradução, a classe e as duas frases válidas.', 'error');
if ($action === 'import_csv' && (!isset($_FILES['csv_file']) || !is_array($_FILES['csv_file']) || (int) ($_FILES['csv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK || !is_uploaded_file((string) ($_FILES['csv_file']['tmp_name'] ?? '')))) wordsRedirect('Envie uma planilha CSV válida.', 'error');

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
    if ($action === 'import_csv') {
        $imported = importCsvTranslations($pdo, (string) $_FILES['csv_file']['tmp_name']);
        wordsRedirect($imported . ' registro' . ($imported === 1 ? '' : 's') . ' importado' . ($imported === 1 ? '' : 's') . ' com sucesso.');
    }
    if ($action === 'manual_translation') {
        $wordStatement = $pdo->prepare('SELECT id FROM words WHERE id = :id');
        $wordStatement->execute(['id' => $id]);
        if ($wordStatement->fetchColumn() === false) translationsRedirect((int) $id, 'Palavra não encontrada.', 'error');
        $pdo->beginTransaction();
        $findTranslation = $pdo->prepare('SELECT id FROM translations WHERE id_word = :id_word AND portugues = :portugues ORDER BY id ASC LIMIT 1');
        $findTranslation->execute(['id_word' => $id, 'portugues' => $translation]);
        $existingTranslationId = $findTranslation->fetchColumn();
        if ($existingTranslationId === false) {
            $insertTranslation = $pdo->prepare('INSERT INTO translations (id_word, portugues, `type`) VALUES (:id_word, :portugues, :type)');
            $insertTranslation->execute(['id_word' => $id, 'portugues' => $translation, 'type' => $translationType]);
            $existingTranslationId = (int) $pdo->lastInsertId();
        }
        $insertSentence = $pdo->prepare('INSERT INTO frases (id_translation, frase_portugues, frase_ingles) VALUES (:id_translation, :frase_portugues, :frase_ingles)');
        $insertSentence->execute(['id_translation' => $existingTranslationId, 'frase_portugues' => $portugueseSentence, 'frase_ingles' => $englishSentence]);
        $pdo->commit();
        translationsRedirect((int) $id, 'Tradução e frase adicionadas com sucesso.');
    }
    if ($action === 'generate_more') {
        $translationStatement = $pdo->prepare('SELECT translations.id, words.word, translations.portugues FROM translations INNER JOIN words ON words.id = translations.id_word WHERE translations.id = :translation_id AND translations.id_word = :word_id');
        $translationStatement->execute(['translation_id' => $translationId, 'word_id' => $id]);
        $storedTranslation = $translationStatement->fetch(PDO::FETCH_ASSOC);
        if (!$storedTranslation) translationsRedirect((int) $id, 'Tradução não encontrada.', 'error');

        $sentencesStatement = $pdo->prepare('SELECT frase_portugues, frase_ingles FROM frases WHERE id_translation = :translation_id ORDER BY id ASC');
        $sentencesStatement->execute(['translation_id' => $translationId]);
        $existingSentences = $sentencesStatement->fetchAll(PDO::FETCH_ASSOC);
        $missingSentences = max(0, 10 - count($existingSentences));
        if ($missingSentences === 0) translationsRedirect((int) $id, 'Esta tradução já possui 10 frases de exemplo.');

        $newSentences = generateAdditionalSentences($storedTranslation['word'], $storedTranslation['portugues'], $existingSentences, $missingSentences);
        $pdo->beginTransaction();
        $insertSentence = $pdo->prepare('INSERT INTO frases (id_translation, frase_portugues, frase_ingles) VALUES (:id_translation, :frase_portugues, :frase_ingles)');
        foreach ($newSentences as $sentence) {
            $insertSentence->execute(['id_translation' => $translationId, 'frase_portugues' => $sentence['frase_portugues'], 'frase_ingles' => $sentence['frase_ingles']]);
        }
        $pdo->commit();
        translationsRedirect((int) $id, count($newSentences) . ' novas frases geradas para “' . $storedTranslation['portugues'] . '”.');
    }
    if ($action === 'generate_audio') {
        $sentencesStatement = $pdo->query("SELECT id, frase_portugues, frase_ingles, audio_portugues, audio_en_gb FROM frases WHERE audio_portugues IS NULL OR audio_portugues = '' OR audio_en_gb IS NULL OR audio_en_gb = '' ORDER BY id ASC");
        $sentences = $sentencesStatement->fetchAll(PDO::FETCH_ASSOC);
        if ($sentences === []) wordsRedirect('Todas as frases já possuem áudio.');

        $updateAudio = $pdo->prepare('UPDATE frases SET audio_portugues = :audio_portugues, audio_en_gb = :audio_en_gb WHERE id = :id');
        foreach ($sentences as $sentence) {
            $portugueseAudio = (string) ($sentence['audio_portugues'] ?? '');
            $englishAudio = (string) ($sentence['audio_en_gb'] ?? '');
            if ($englishAudio === '') $englishAudio = synthesizeSentenceAudio($sentence['frase_ingles'], 'en-GB', 'en-GB-Chirp3-HD-Algieba');
            if ($portugueseAudio === '') $portugueseAudio = synthesizeSentenceAudio($sentence['frase_portugues'], 'pt-BR', 'pt-BR-Chirp3-HD-Algieba');
            $updateAudio->execute(['id' => $sentence['id'], 'audio_portugues' => $portugueseAudio, 'audio_en_gb' => $englishAudio]);
        }
        wordsRedirect(count($sentences) . ' frase' . (count($sentences) === 1 ? '' : 's') . ' com áudio gerado.');
    }
    if ($action === 'discover') {
        $wordStatement = $pdo->prepare('SELECT word FROM words WHERE id = :id');
        $wordStatement->execute(['id' => $id]);
        $storedWord = $wordStatement->fetchColumn();
        if (!is_string($storedWord)) translationsRedirect((int) $id, 'Palavra não encontrada.', 'error');
        $discovery = discoverTranslations($storedWord);
        $pdo->beginTransaction();
        $insertExpression = $pdo->prepare('INSERT INTO words (word) VALUES (:word) ON DUPLICATE KEY UPDATE word = word');
        foreach ($discovery['relatedExpressions'] as $expression) {
            $insertExpression->execute(['word' => $expression]);
        }
        $pdo->prepare('DELETE FROM translations WHERE id_word = :id')->execute(['id' => $id]);
        $insert = $pdo->prepare('INSERT INTO translations (id_word, portugues, `type`) VALUES (:id_word, :portugues, :type)');
        $insertSentence = $pdo->prepare('INSERT INTO frases (id_translation, frase_portugues, frase_ingles) VALUES (:id_translation, :frase_portugues, :frase_ingles)');
        foreach ($discovery['translations'] as $translation) {
            $insert->execute(['id_word' => $id, 'portugues' => $translation['portugues'], 'type' => $translation['type']]);
            $translationId = (int) $pdo->lastInsertId();
            foreach ($translation['sentences'] as $sentence) {
                $insertSentence->execute(['id_translation' => $translationId, 'frase_portugues' => $sentence['frase_portugues'], 'frase_ingles' => $sentence['frase_ingles']]);
            }
        }
        $pdo->commit();
        $expressionCount = count($discovery['relatedExpressions']);
        $message = count($discovery['translations']) . ' traduções encontradas e salvas.';
        if ($expressionCount > 0) $message .= ' ' . $expressionCount . ' phrasal verb' . ($expressionCount === 1 ? '' : 's') . ' ou locuções adicionado' . ($expressionCount === 1 ? '' : 's') . ' à lista de palavras.';
        translationsRedirect((int) $id, $message);
    }
    wordsRedirect('Ação inválida.', 'error');
} catch (Throwable $exception) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    if ($exception->getCode() === '23000') wordsRedirect('Essa palavra já está cadastrada.', 'error');
    error_log('Subdrill words database error: ' . $exception->getMessage());
    wordsRedirect('Não foi possível salvar a palavra agora. Tente novamente mais tarde.', 'error');
}
