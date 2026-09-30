<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settlement.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];

$balances    = calculate_balances($tripId);
$settlements = calculate_settlements($balances);
$total       = get_trip_spent($tripId);

$pageTitle = 'Split Bill · ' . $trip['name'];
$activeTab = 'settlement';
$pageScripts = ['settlement.js'];
$jsData = ['tripId' => $tripId];
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
?>
<div id="settlementApp" data-trip-id="<?= $tripId ?>">
  <div class="card total-card">
    <span class="stat-label">Trip Expenses</span>
    <span class="stat-value" id="settleTotal"><?= e(rupiah($total)) ?></span>
  </div>

  <h2>Balances</h2>
  <div class="grid-cards" id="balanceList">
    <?php foreach ($balances as $b):
        $cls = $b['balance'] > 0 ? 'pos' : ($b['balance'] < 0 ? 'neg' : 'zero'); ?>
      <div class="card balance-card <?= $cls ?>" data-user-id="<?= (int)$b['user_id'] ?>"
           data-paid="<?= e($b['paid']) ?>" data-share="<?= e($b['share']) ?>" data-balance="<?= e($b['balance']) ?>">
        <div class="member-line"><span class="avatar"><?= e(initials($b['name'])) ?></span><strong><?= e($b['name']) ?></strong></div>
        <div class="row-between small"><span class="muted">Paid</span><span><?= e(rupiah($b['paid'])) ?></span></div>
        <div class="row-between small"><span class="muted">Share</span><span><?= e(rupiah($b['share'])) ?></span></div>
        <div class="row-between balance-line"><span>Balance</span><strong><?= e(signed_rupiah($b['balance'])) ?></strong></div>
      </div>
    <?php endforeach; ?>
  </div>
  <div class="row-between page-head"><h2>Settlement</h2><button type="button" class="btn btn-outline btn-sm" id="copySummary" hidden>Copy summary</button></div>
  <div class="card" id="settlementList">
    <?php if ($total <= 0): ?>
      <p class="muted">Belum ada pengeluaran yang dicatat.</p>
    <?php elseif (!$settlements): ?>
      <p>🎉 Semua sudah impas. Tidak ada yang perlu membayar.</p>
    <?php else: foreach ($settlements as $s): ?>
      <div class="settle-item">
        <span><strong><?= e($s['from']) ?></strong> <span class="arrow">→</span> <strong><?= e($s['to']) ?></strong></span>
        <strong class="settle-amount"><?= e(rupiah($s['amount'])) ?></strong>
      </div>
    <?php endforeach; endif; ?>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
