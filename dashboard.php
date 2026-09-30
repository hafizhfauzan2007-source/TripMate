<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$stats = get_dashboard_stats(user_id());
$trips = get_user_trips(user_id());
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';
?>
<div class="row-between page-head">
  <div>
    <h1>My Trips</h1>
    <p class="muted">Halo, <?= e($_SESSION['user_name'] ?? '') ?>! Ini ringkasan perjalananmu.</p>
  </div>
  <a href="<?= BASE_URL ?>/trip/create.php" class="btn btn-primary">+ Create New Trip</a>
</div>

<div class="stat-grid">
  <div class="card stat-card"><span class="stat-label">Total Trips</span><span class="stat-value"><?= $stats['total'] ?></span></div>
  <div class="card stat-card"><span class="stat-label">Active Trips</span><span class="stat-value"><?= $stats['active'] ?></span></div>
  <div class="card stat-card"><span class="stat-label">Upcoming Trips</span><span class="stat-value"><?= $stats['upcoming'] ?></span></div>
  <div class="card stat-card"><span class="stat-label">Total Spent</span><span class="stat-value"><?= e(rupiah($stats['spent'])) ?></span></div>
</div>

<?php if (!$trips): ?>
  <div class="card empty-state">
    <div class="empty-icon">🧳</div>
    <h3>Belum ada perjalanan</h3>
    <p class="muted">Buat perjalanan pertamamu dan ajak teman-teman.</p>
    <a href="<?= BASE_URL ?>/trip/create.php" class="btn btn-primary">Create Trip</a>
  </div>
<?php else: ?>
  <div class="grid-cards">
    <?php foreach ($trips as $t): ?>
      <article class="card trip-card">
        <div class="row-between">
          <span class="badge badge-<?= e($t['status']) ?>"><?= e(status_label($t['status'])) ?></span>
          <span class="small muted"><?= e(ucfirst($t['role'])) ?></span>
        </div>
        <h3><?= e($t['name']) ?></h3>
        <p class="muted small">📍 <?= e($t['destination']) ?></p>
        <ul class="trip-meta">
          <li>📅 <?= e(format_date_range($t['start_date'], $t['end_date'])) ?></li>
          <li>👥 <?= (int)$t['member_count'] ?> Member<?= $t['member_count'] > 1 ? 's' : '' ?></li>
          <li>💰 <?= e(rupiah($t['spent'])) ?> spent</li>
        </ul>
        <a href="<?= BASE_URL ?>/trip/overview.php?id=<?= (int)$t['id'] ?>" class="btn btn-outline btn-block">View Trip</a>
      </article>
    <?php endforeach; ?>
  </div>
<?php endif; ?>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
