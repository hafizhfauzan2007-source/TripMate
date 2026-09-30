<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$old = take_old(['email']);
$pageTitle = 'Login';
$mainClass = 'container auth-page';
$pageScripts = ['auth.js'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="card auth-card">
  <h1>Welcome back</h1>
  <p class="muted">Login untuk melanjutkan perjalananmu.</p>
  <form method="post" action="<?= BASE_URL ?>/actions/auth.php" class="form" id="loginForm" data-validate="login">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="login">
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="nama@email.com" required autofocus>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Password" required>
    </div>
    <p class="field-error" id="formError" hidden></p>
    <button type="submit" class="btn btn-primary btn-block btn-lg">Login</button>
  </form>
  <p class="auth-switch">Belum punya akun? <a href="<?= BASE_URL ?>/register.php">Register</a></p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
