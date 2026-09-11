<?php
declare(strict_types=1);
$user = currentUser();
appShellHeader('Painel', 'dashboard');
?>
<section class="dashboard">
  <section class="hero">
    <p class="eyebrow">PAINEL</p>
    <h1>Olá, <?= htmlspecialchars($user['username'] ?? 'você') ?>.</h1>
    <p>Seu acesso está protegido e isolado das outras aplicações desta hospedagem.</p>
  </section>
  <section class="stat-grid" aria-label="Resumo de estudos">
    <article><small>Trilhas ativas</small><strong>0</strong></article>
    <article><small>Itens estudados</small><strong>0</strong></article>
    <article><small>Próxima sessão</small><strong>—</strong></article>
  </section>
</section>
<?php appShellFooter();
