<?php
require_once __DIR__ . '/functions.php';
$pageTitle = $pageTitle ?? 'TripMate';
$mainClass = $mainClass ?? 'container page';
$flashMsg  = flash();
$firstName = is_logged_in() ? explode(' ', $_SESSION['user_name'] ?? '')[0] : '';
?>
<!DOCTYPE html>
<html lang="id">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> · TripMate</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/css/style.css">
</head>
<body>
<header class="navbar">
  <div class="container navbar-inner">
    <a href="<?= BASE_URL ?>/index.php" class="logo"><span class="logo-icon">✈</span> TripMate</a>
    <input type="checkbox" id="nav-toggle" class="nav-toggle">
    <label for="nav-toggle" class="nav-burger" aria-label="Menu">☰</label>
    <nav class="nav-links">
      <?php if (is_logged_in()): ?>
        <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
        <a href="<?= BASE_URL ?>/trip/create.php">+ New Trip</a>
        <span class="nav-user">Hi, <?= e($firstName) ?></span>
        <form action="<?= BASE_URL ?>/actions/auth.php" method="post" class="inline-form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="logout">
          <button type="submit" class="btn btn-outline btn-sm">Logout</button>
        </form>
      <?php else: ?>
        <a href="<?= BASE_URL ?>/index.php#features">Features</a>
        <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-sm">Login</a>
        <a href="<?= BASE_URL ?>/register.php" class="btn btn-primary btn-sm">Register</a>
      <?php endif; ?>
    </nav>
  </div>
</header>

<?php if ($flashMsg): ?>
  <div class="container flash-wrap">
    <div class="alert alert-<?= e($flashMsg['type']) ?>" role="alert"><?= e($flashMsg['message']) ?></div>
  </div>
<?php endif; ?>

<main class="<?= e($mainClass) ?>">
