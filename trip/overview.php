<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];
$pdo = db();

$spent     = get_trip_spent($tripId);
$budget    = (float)$trip['budget'];
$remaining = $budget - $spent;
$pct       = $budget > 0 ? min(100, (int)round($spent / $budget * 100)) : 0;
$members   = get_trip_members($tripId);

$st = $pdo->prepare('SELECT * FROM itineraries WHERE trip_id = ? ORDER BY day_date, start_time LIMIT 5');
$st->execute([$tripId]);
$nextItems = $st->fetchAll();

$st = $pdo->prepare('SELECT e.*, u.name AS paid_name FROM expenses e JOIN users u ON u.id = e.paid_by
                     WHERE e.trip_id = ? ORDER BY e.expense_date DESC, e.id DESC LIMIT 5');
$st->execute([$tripId]);
$recent = $st->fetchAll();

$st = $pdo->prepare('SELECT COUNT(*) total, COALESCE(SUM(is_checked),0) done FROM checklists WHERE trip_id = ?');
$st->execute([$tripId]);
$ck = $st->fetch();
$ckPct = $ck['total'] ? (int)round($ck['done'] / $ck['total'] * 100) : 0;

$pageTitle = $trip['name'];
$activeTab = 'overview';
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
?>
<div class="stat-grid">
  <div class="card stat-card"><span class="stat-label">Budget</span><span class="stat-value"><?= e(rupiah($budget)) ?></span></div>
  <div class="card stat-card"><span class="stat-label">Spent</span><span class="stat-value"><?= e(rupiah($spent)) ?></span></div>
  <div class="card stat-card">
    <span class="stat-label">Remaining</span>
    <span class="stat-value <?= $remaining < 0 ? 'text-danger' : 'text-success' ?>"><?= e($remaining < 0 ? '-' . rupiah(abs($remaining)) : rupiah($remaining)) ?></span>
  </div>
  <div class="card stat-card"><span class="stat-label">Members</span><span class="stat-value"><?= count($members) ?> People</span></div>
</div>

<div class="card">
  <div class="row-between"><strong>Budget usage</strong><span class="small muted"><?= $budget > 0 ? $pct . '%' : 'Budget belum diatur' ?></span></div>
  <div class="progress"><div class="progress-bar <?= $remaining < 0 ? 'over' : '' ?>" style="width:<?= $pct ?>%"></div></div>
  <?php if ($remaining < 0): ?><p class="small text-danger">Pengeluaran melebihi budget.</p><?php endif; ?>
</div>

<div class="grid-2">
  <div class="card">
    <div class="row-between"><h3>Itinerary</h3><a href="<?= BASE_URL ?>/trip/itinerary.php?id=<?= $tripId ?>" class="small">View all →</a></div>
    <?php if (!$nextItems): ?>
      <p class="muted">Belum ada itinerary.</p>
    <?php else: foreach ($nextItems as $it): ?>
      <div class="mini-row">
        <div><strong><?= e(substr($it['start_time'], 0, 5)) ?></strong> <span class="muted small"><?= e(format_date($it['day_date'], 'j M')) ?></span></div>
        <div class="grow"><?= e($it['activity']) ?><?php if ($it['location']): ?><div class="small muted">📍 <?= e($it['location']) ?></div><?php endif; ?></div>
      </div>
    <?php endforeach; endif; ?>
  </div>

  <div class="card">
    <div class="row-between"><h3>Recent Expenses</h3><a href="<?= BASE_URL ?>/trip/expenses.php?id=<?= $tripId ?>" class="small">View all →</a></div>
    <?php if (!$recent): ?>
      <p class="muted">Belum ada pengeluaran.</p>
    <?php else: foreach ($recent as $ex): ?>
      <div class="mini-row">
        <div class="grow"><?= e($ex['name']) ?><div class="small muted">Paid by <?= e($ex['paid_name']) ?> · <?= e(format_date($ex['expense_date'], 'j M')) ?></div></div>
        <strong><?= e(rupiah($ex['amount'])) ?></strong>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>

<div class="grid-2">
  <div class="card">
    <div class="row-between"><h3>Packing Checklist</h3><a href="<?= BASE_URL ?>/trip/checklist.php?id=<?= $tripId ?>" class="small">Open →</a></div>
    <div class="progress"><div class="progress-bar" style="width:<?= $ckPct ?>%"></div></div>
    <p class="small muted"><?= (int)$ck['done'] ?> of <?= (int)$ck['total'] ?> items · <?= $ckPct ?>%</p>
  </div>
  <div class="card">
    <h3>About this trip</h3>
    <p class="<?= $trip['description'] ? '' : 'muted' ?>"><?= $trip['description'] ? nl2br(e($trip['description'])) : 'Belum ada deskripsi.' ?></p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
