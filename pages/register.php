<?php
declare(strict_types=1);

pageHeader('Criar conta');
$error = $_SESSION['auth_error'] ?? '';
$old = $_SESSION['register_old'] ?? ['username' => ''];
unset($_SESSION['auth_error'], $_SESSION['register_old']);
?>
<main class="login-shell">
  <section class="brand-panel">
    <div class="brand-mark">S</div>
    <p class="eyebrow">SUBDRILL</p>
    <h1>Comece a aprender<br>mais fundo.</h1>
    <p class="muted">Crie sua conta para organizar seus estudos, montar trilhas e acompanhar cada descoberta.</p>
    <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
  </section>
  <section class="form-panel">
    <div class="form-wrap">
      <a class="logo" href="<?= htmlspecialchars(appUrl()) ?>"><span>◈</span> Subdrill</a>
      <div class="form-title"><p class="eyebrow">PRIMEIROS PASSOS</p><h2>Crie sua conta</h2><p>Leva apenas alguns instantes.</p></div>
      <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form action="<?= htmlspecialchars(appUrl('api/auth/register.php')) ?>" method="post" class="login-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
        <label>Usuário<input name="username" type="text" autocomplete="username" required maxlength="50" value="<?= htmlspecialchars((string) $old['username']) ?>" placeholder="seu_usuario"></label>
        <label>Senha<input name="password" type="password" autocomplete="new-password" required minlength="8" placeholder="Mínimo de 8 caracteres"></label>
        <button class="button" type="submit">Criar conta <span>→</span></button>
      </form>
      <p class="help">Já tem uma conta? <a href="<?= htmlspecialchars(appUrl('login')) ?>">Entrar</a></p>
    </div>
  </section>
</main>
<?php pageFooter();
