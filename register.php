<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) redirect('dashboard.php');
$old = take_old(['name', 'email']);
$pageTitle = 'Register';
$mainClass = 'container auth-page';
$pageScripts = ['auth.js'];
require_once __DIR__ . '/includes/header.php';
?>
<div class="card auth-card">
  <h1>Create your account</h1>
  <p class="muted">Daftar gratis dan mulai rencanakan perjalanan.</p>
  <form method="post" action="<?= BASE_URL ?>/actions/auth.php" class="form" id="registerForm" data-validate="register">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="register">
    <div class="field">
      <label for="name">Name</label>
      <input type="text" id="name" name="name" value="<?= e($old['name'] ?? '') ?>" placeholder="Nama lengkap" maxlength="100" required autofocus>
    </div>
    <div class="field">
      <label for="email">Email</label>
      <input type="email" id="email" name="email" value="<?= e($old['email'] ?? '') ?>" placeholder="nama@email.com" required>
    </div>
    <div class="field">
      <label for="password">Password</label>
      <input type="password" id="password" name="password" placeholder="Minimal 6 karakter" minlength="6" required>
    </div>
    <div class="field">
      <label for="confirm_password">Confirm Password</label>
      <input type="password" id="confirm_password" name="confirm_password" placeholder="Ulangi password" minlength="6" required>
    </div>
    <p class="field-error" id="formError" hidden></p>
    <button type="submit" class="btn btn-primary btn-block btn-lg">Register</button>
  </form>
  <p class="auth-switch">Sudah punya akun? <a href="<?= BASE_URL ?>/login.php">Login</a></p>
</div>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
