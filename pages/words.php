<?php
declare(strict_types=1);

$search = trim((string) ($_GET['q'] ?? ''));
$selectedWordId = filter_var($_GET['word'] ?? null, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 10;
$flash = $_SESSION['words_flash'] ?? null;
unset($_SESSION['words_flash']);
$words = [];
$total = 0;
$error = '';
$selectedWord = null;
$translations = [];

try {
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $where = $search === '' ? '' : ' WHERE word LIKE :search';
    $count = $pdo->prepare('SELECT COUNT(*) FROM words' . $where);
    if ($search !== '') $count->bindValue(':search', '%' . $search . '%');
    $count->execute();
    $total = (int) $count->fetchColumn();
    $pages = max(1, (int) ceil($total / $perPage));
    $page = min($page, $pages);
    $statement = $pdo->prepare('SELECT id, word FROM words' . $where . ' ORDER BY id DESC LIMIT :limit OFFSET :offset');
    if ($search !== '') $statement->bindValue(':search', '%' . $search . '%');
    $statement->bindValue(':limit', $perPage, PDO::PARAM_INT);
    $statement->bindValue(':offset', ($page - 1) * $perPage, PDO::PARAM_INT);
    $statement->execute();
    $words = $statement->fetchAll(PDO::FETCH_ASSOC);
    if ($selectedWordId) {
        $selectedStatement = $pdo->prepare('SELECT id, word FROM words WHERE id = :id');
        $selectedStatement->execute(['id' => $selectedWordId]);
        $selectedWord = $selectedStatement->fetch(PDO::FETCH_ASSOC) ?: null;
        if ($selectedWord) {
            $translationStatement = $pdo->prepare('SELECT translations.id, translations.portugues, translations.`type`, frases.frase_portugues, frases.frase_ingles FROM translations LEFT JOIN frases ON frases.id_translation = translations.id WHERE translations.id_word = :id ORDER BY translations.`type` ASC, translations.portugues ASC, frases.id ASC');
            $translationStatement->execute(['id' => $selectedWordId]);
            foreach ($translationStatement->fetchAll(PDO::FETCH_ASSOC) as $row) {
                $translationId = (int) $row['id'];
                if (!isset($translations[$translationId])) {
                    $translations[$translationId] = ['id' => $translationId, 'portugues' => $row['portugues'], 'type' => (int) $row['type'], 'sentences' => []];
                }
                if ($row['frase_portugues'] !== null && $row['frase_ingles'] !== null) {
                    $translations[$translationId]['sentences'][] = ['portugues' => $row['frase_portugues'], 'ingles' => $row['frase_ingles']];
                }
            }
        }
    }
} catch (PDOException $exception) {
    error_log('Subdrill words page database error: ' . $exception->getMessage());
    $error = 'Não foi possível carregar as palavras agora.';
    $pages = 1;
}

$pageUrl = static fn(int $target): string => appUrl('words') . '?' . http_build_query(array_filter(['q' => $search, 'page' => $target], static fn($value) => $value !== '' && $value !== 1));
$wordUrl = static fn(int $id): string => appUrl('words') . '?word=' . $id;
$translationTypes = [1 => 'Verbo / phrasal verb / locução verbal', 2 => 'Substantivo / locução substantiva', 3 => 'Conjunção / locução conjuntiva', 4 => 'Advérbio / locução adverbial', 5 => 'Adjetivo / locução adjetiva', 6 => 'Preposição / locução prepositiva'];
appShellHeader('Words', 'words');
?>
<section class="words-page">
  <header class="words-header"><div><p class="eyebrow">VOCABULÁRIO</p><h1>Words</h1><p>Gerencie as palavras disponíveis para os seus estudos.</p></div><div class="words-header-actions"><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="generate_audio"><button class="icon-button audio-generate-button" type="submit" aria-label="Gerar áudios das frases sem áudio" title="Gerar áudios pendentes">♫</button></form><button class="icon-button" type="button" data-modal-open="create-word" aria-label="Adicionar palavra">+</button></div></header>
  <?php if ($flash): ?><div class="alert <?= $flash['type'] === 'success' ? 'alert-success' : '' ?>" role="alert"><?= htmlspecialchars($flash['message']) ?></div><?php endif; ?>
  <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
  <form class="word-search" method="get" action="<?= htmlspecialchars(appUrl('words')) ?>"><label for="word-search">Pesquisar palavras</label><input id="word-search" name="q" type="search" value="<?= htmlspecialchars($search) ?>" placeholder="Digite para pesquisar" autocomplete="off"><noscript><button class="button" type="submit">Pesquisar</button></noscript></form>
  <section class="words-card" aria-label="Lista de palavras"><div class="words-count"><?= $total ?> <?= $total === 1 ? 'palavra encontrada' : 'palavras encontradas' ?></div>
    <?php if ($words): ?><ul class="words-list"><?php foreach ($words as $item): ?><li><a class="word-link" href="<?= htmlspecialchars($wordUrl((int) $item['id'])) ?>"><?= htmlspecialchars($item['word']) ?></a><div class="word-actions"><button class="text-button" type="button" data-modal-open="edit-word" data-id="<?= (int) $item['id'] ?>" data-word="<?= htmlspecialchars($item['word'], ENT_QUOTES) ?>">Editar</button><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>" onsubmit="return confirm('Remover a palavra <?= htmlspecialchars($item['word'], ENT_QUOTES) ?>?');"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $item['id'] ?>"><button class="text-button danger" type="submit">Remover</button></form></div></li><?php endforeach; ?></ul><?php else: ?><div class="empty-state"><strong>Nenhuma palavra encontrada.</strong><span>Adicione uma palavra ou ajuste a pesquisa.</span></div><?php endif; ?>
  </section>
  <?php if ($selectedWord): ?><section class="translations-card" aria-labelledby="translations-title"><header><div><p class="eyebrow">TRADUÇÕES</p><h2 id="translations-title"><?= htmlspecialchars($selectedWord['word']) ?></h2><p>Traduções armazenadas para esta palavra ou expressão exata.</p></div><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="discover"><input type="hidden" name="id" value="<?= (int) $selectedWord['id'] ?>"><button class="button" type="submit">Descobrir traduções</button></form></header><?php if ($translations): ?><ul class="translations-list"><?php foreach ($translations as $translation): ?><li><div class="translation-content"><div class="translation-heading"><div><strong><?= htmlspecialchars($translation['portugues']) ?></strong><small><?= htmlspecialchars($translationTypes[$translation['type']] ?? 'Classe desconhecida') ?></small></div><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="generate_more"><input type="hidden" name="id" value="<?= (int) $selectedWord['id'] ?>"><input type="hidden" name="translation_id" value="<?= $translation['id'] ?>"><button class="text-button" type="submit">Gerar mais</button></form></div><span class="translation-sentence-count"><?= count($translation['sentences']) ?> de 10 frases de exemplo</span><?php if ($translation['sentences']): ?><div class="translation-examples"><?php foreach ($translation['sentences'] as $sentence): ?><div class="translation-example"><span lang="en"><?= htmlspecialchars($sentence['ingles']) ?></span><span><?= htmlspecialchars($sentence['portugues']) ?></span></div><?php endforeach; ?></div><?php else: ?><span class="translation-example-missing">Exemplo ainda não disponível. Use “Gerar mais” para criar as frases.</span><?php endif; ?></div></li><?php endforeach; ?></ul><?php else: ?><div class="empty-state"><strong>Nenhuma tradução descoberta ainda.</strong><span>Use “Descobrir traduções” para consultar o Gemini.</span></div><?php endif; ?></section><?php elseif ($selectedWordId): ?><div class="alert" role="alert">Palavra não encontrada.</div><?php endif; ?>
  <?php if ($pages > 1): ?><nav class="pagination" aria-label="Paginação de palavras"><a class="text-button <?= $page === 1 ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(max(1, $page - 1))) ?>" <?= $page === 1 ? 'aria-disabled="true" tabindex="-1"' : '' ?>>← Anterior</a><span>Página <?= $page ?> de <?= $pages ?></span><a class="text-button <?= $page === $pages ? 'is-disabled' : '' ?>" href="<?= htmlspecialchars($pageUrl(min($pages, $page + 1))) ?>" <?= $page === $pages ? 'aria-disabled="true" tabindex="-1"' : '' ?>>Próxima →</a></nav><?php endif; ?>
</section>
<dialog class="word-modal" id="create-word" aria-labelledby="create-title"><form method="dialog"><button class="modal-close" aria-label="Fechar">×</button></form><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="create"><h2 id="create-title">Adicionar palavra</h2><p>Espaços são aceitos em nomes compostos.</p><label>Palavra<input name="word" required maxlength="150" autofocus></label><button class="button" type="submit">Adicionar</button></form></dialog>
<dialog class="word-modal" id="edit-word" aria-labelledby="edit-title"><form method="dialog"><button class="modal-close" aria-label="Fechar">×</button></form><form method="post" action="<?= htmlspecialchars(appUrl('words')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="action" value="update"><input type="hidden" name="id"><h2 id="edit-title">Editar palavra</h2><label>Palavra<input name="word" required maxlength="150"></label><button class="button" type="submit">Salvar alterações</button></form></dialog>
<script>document.querySelectorAll('[data-modal-open]').forEach(button=>button.addEventListener('click',()=>{const modal=document.getElementById(button.dataset.modalOpen);if(button.dataset.id){modal.querySelector('[name="id"]').value=button.dataset.id;modal.querySelector('[name="word"]').value=button.dataset.word;}modal.showModal();modal.querySelector('[name="word"]').focus();}));let timer;document.getElementById('word-search').addEventListener('input',event=>{clearTimeout(timer);timer=setTimeout(()=>event.target.form.submit(),300);});</script>
<?php appShellFooter();
