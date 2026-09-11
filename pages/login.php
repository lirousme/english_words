<?php
declare(strict_types=1);

pageHeader('Entrar');
$error = $_SESSION['auth_error'] ?? '';
$username = $_SESSION['login_username'] ?? '';
unset($_SESSION['auth_error'], $_SESSION['login_username']);
?>
<main class="login-shell">
  <section class="brand-panel">
    <div class="brand-mark">S</div>
    <p class="eyebrow">SUBDRILL</p>
    <h1>Seu espaço para<br>aprender mais fundo.</h1>
    <p class="muted">Organize seus estudos, crie trilhas e acompanhe cada descoberta em um único lugar.</p>
    <div class="orbit orbit-one"></div><div class="orbit orbit-two"></div>
  </section>
  <section class="form-panel">
    <div class="form-wrap">
      <a class="logo" href="<?= htmlspecialchars(appUrl()) ?>"><span>◈</span> Subdrill</a>
      <div class="form-title"><p class="eyebrow">BEM-VINDO DE VOLTA</p><h2>Entre na sua conta</h2><p>Continue de onde você parou.</p></div>
      <?php if ($error): ?><div class="alert" role="alert"><?= htmlspecialchars($error) ?></div><?php endif; ?>
      <form action="<?= htmlspecialchars(appUrl('api/auth/login.php')) ?>" method="post" class="login-form">
        <input type="hidden" name="csrf" value="<?= htmlspecialchars(csrfToken()) ?>">
        <label>Usuário<input name="username" type="text" autocomplete="username" required maxlength="50" value="<?= htmlspecialchars((string) $username) ?>" placeholder="seu_usuario"></label>
        <label>Senha<input name="password" type="password" autocomplete="current-password" required placeholder="••••••••"></label>
        <button class="button" type="submit">Entrar <span>→</span></button>
      </form>
      <p class="help">Ainda não tem uma conta? <a href="<?= htmlspecialchars(appUrl('criar-conta')) ?>">Criar conta</a></p>
    </div>
  </section>
</main>
<?php pageFooter();
