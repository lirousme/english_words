<?php
declare(strict_types=1);

$user = currentUser() ?? [];
$sentences = [];
$translation = null;
$error = '';
unset($_SESSION['play_flash']);

try {
    $pdo = new PDO('mysql:host=' . env('DB_HOST') . ';dbname=' . env('DB_NAME') . ';charset=utf8mb4', env('DB_USER'), env('DB_PASS'), [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => false]);
    $userId = (int) ($user['id'] ?? 0);
    $due = $pdo->prepare('SELECT t.id, t.portugues FROM reviews r INNER JOIN translations t ON t.id = r.id_translation WHERE r.id_user = :user_id AND r.next_review <= NOW() AND EXISTS (SELECT 1 FROM frases f WHERE f.id_translation = t.id) ORDER BY r.next_review ASC, r.id ASC LIMIT 1');
    $due->execute(['user_id' => $userId]);
    $translation = $due->fetch(PDO::FETCH_ASSOC) ?: null;
    if (!$translation) {
        $new = $pdo->prepare('SELECT t.id, t.portugues FROM translations t WHERE NOT EXISTS (SELECT 1 FROM reviews r WHERE r.id_user = :user_id AND r.id_translation = t.id) AND EXISTS (SELECT 1 FROM frases f WHERE f.id_translation = t.id) ORDER BY t.id ASC LIMIT 1');
        $new->execute(['user_id' => $userId]);
        $translation = $new->fetch(PDO::FETCH_ASSOC) ?: null;
    }
    if ($translation) {
        $statement = $pdo->prepare('SELECT frase_portugues, frase_ingles, audio_portugues, audio_en_gb FROM frases WHERE id_translation = :id ORDER BY id ASC');
        $statement->execute(['id' => $translation['id']]);
        $sentences = $statement->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $exception) {
    error_log('Subdrill play page database error: ' . $exception->getMessage());
    $error = 'Não foi possível carregar sua revisão agora.';
}

appShellHeader('Jogar', 'play');
?>
<section class="play-page">
  <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php elseif ($translation && $sentences): ?>
    <section class="review-card" aria-labelledby="review-title">
      <header><div><label class="auto-play-toggle" for="auto-play"><span>Automático</span><input id="auto-play" type="checkbox" role="switch" aria-label="Avançar slides automaticamente após os áudios" checked><span class="auto-play-track" aria-hidden="true"></span></label><h2 id="review-title"><?= htmlspecialchars($translation['portugues']) ?></h2></div><span id="slide-counter" aria-live="polite">1 de <?= count($sentences) ?></span></header>
      <div class="review-slides">
        <?php foreach ($sentences as $index => $sentence): ?><article class="review-slide<?= $index === 0 ? ' is-active' : '' ?>" data-slide data-audio-english="<?= htmlspecialchars((string) ($sentence['audio_en_gb'] ?? ''), ENT_QUOTES) ?>" data-audio-portuguese="<?= htmlspecialchars((string) ($sentence['audio_portugues'] ?? ''), ENT_QUOTES) ?>" aria-hidden="<?= $index === 0 ? 'false' : 'true' ?>"><p class="review-portuguese"><?= htmlspecialchars($sentence['frase_portugues']) ?></p><p class="review-english" lang="en"><?= htmlspecialchars($sentence['frase_ingles']) ?></p></article><?php endforeach; ?>
      </div>
      <footer class="review-controls"><button class="button button-secondary" id="previous-slide" type="button" disabled>Previous</button><form id="review-form" method="post" action="<?= htmlspecialchars(appUrl('jogar')) ?>"><input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>"><input type="hidden" name="translation_id" value="<?= (int) $translation['id'] ?>"><button class="button" id="next-slide" type="button">Next</button></form></footer>
    </section>
    <script>
      (() => {
        const slides = [...document.querySelectorAll('[data-slide]')];
        const previous = document.getElementById('previous-slide');
        const next = document.getElementById('next-slide');
        const form = document.getElementById('review-form');
        const counter = document.getElementById('slide-counter');
        const autoPlay = document.getElementById('auto-play');
        const autoPlayStorageKey = 'subdrill-auto-play';
        let current = 0;
        let activeAudio = null;
        let stopActivePlayback = null;
        let playbackId = 0;

        const savedAutoPlay = localStorage.getItem(autoPlayStorageKey);
        if (savedAutoPlay !== null) autoPlay.checked = savedAutoPlay === 'true';
        autoPlay.addEventListener('change', () => {
          localStorage.setItem(autoPlayStorageKey, String(autoPlay.checked));
        });

        const stopAudio = () => {
          playbackId++;
          if (activeAudio) activeAudio.pause();
          if (stopActivePlayback) stopActivePlayback();
          activeAudio = null;
          stopActivePlayback = null;
        };
        const play = source => new Promise(resolve => {
          if (!source) return resolve();
          const audio = new Audio(`data:audio/mpeg;base64,${source}`);
          activeAudio = audio;
          let finished = false;
          const finish = () => {
            if (finished) return;
            finished = true;
            if (activeAudio === audio) activeAudio = null;
            if (stopActivePlayback === finish) stopActivePlayback = null;
            resolve();
          };
          stopActivePlayback = finish;
          audio.addEventListener('ended', finish, { once: true });
          audio.addEventListener('error', finish, { once: true });
          audio.play().catch(finish);
        });
        const playSlideAudio = async (slide, id) => {
          await play(slide.dataset.audioEnglish);
          if (id !== playbackId || slides[current] !== slide) return;
          await play(slide.dataset.audioPortuguese);
          if (id !== playbackId || slides[current] !== slide || !autoPlay.checked) return;
          if (current === slides.length - 1) form.requestSubmit();
          else {
            current++;
            render();
          }
        };
        const render = () => {
          stopAudio();
          slides.forEach((slide, index) => {
            const active = index === current;
            slide.classList.toggle('is-active', active);
            slide.setAttribute('aria-hidden', String(!active));
          });
          previous.disabled = current === 0;
          counter.textContent = `${current + 1} de ${slides.length}`;
          next.textContent = current === slides.length - 1 ? 'Review' : 'Next';
          const id = playbackId;
          playSlideAudio(slides[current], id);
        };

        previous.addEventListener('click', () => {
          if (current > 0) {
            current--;
            render();
          }
        });
        next.addEventListener('click', () => {
          if (current === slides.length - 1) form.requestSubmit();
          else {
            current++;
            render();
          }
        });
        form.addEventListener('submit', event => {
          if (current !== slides.length - 1) event.preventDefault();
        });
        render();
      })();
    </script>
  <?php else: ?><section class="review-empty"><strong>Nenhuma revisão disponível agora.</strong><span>Quando houver uma tradução vencida ou ainda não estudada com frases de exemplo, ela aparecerá aqui.</span></section><?php endif; ?>
</section>
<?php appShellFooter();
