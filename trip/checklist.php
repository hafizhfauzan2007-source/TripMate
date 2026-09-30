<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];

$st = db()->prepare('SELECT * FROM checklists WHERE trip_id = ? ORDER BY is_checked, id');
$st->execute([$tripId]);
$items = $st->fetchAll();
$total = count($items);
$done  = count(array_filter($items, fn($i) => $i['is_checked']));
$pct   = $total ? (int)round($done / $total * 100) : 0;

// Saran item yang belum ada di daftar
$existing = array_map(fn($i) => mb_strtolower($i['item_name']), $items);
$suggest = array_filter(['KTP', 'Dompet', 'Charger', 'Powerbank', 'Jaket', 'Kamera', 'Obat', 'Sunscreen'],
                        fn($s) => !in_array(mb_strtolower($s), $existing, true));

$pageTitle = 'Checklist · ' . $trip['name'];
$activeTab = 'checklist';
$pageScripts = ['checklist.js'];
$jsData = ['tripId' => $tripId];
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
$action = BASE_URL . '/actions/checklist.php';
?>
<div class="narrow" id="checklistApp" data-trip-id="<?= $tripId ?>">
  <div class="card">
    <div class="row-between"><h3>Packing Checklist</h3><strong id="progressText">Progress: <?= $pct ?>%</strong></div>
    <div class="progress"><div class="progress-bar" id="progressBar" style="width:<?= $pct ?>%"></div></div>
    <p class="small muted" id="progressCount"><?= $done ?> of <?= $total ?> items</p>

    <form method="post" action="<?= $action ?>" class="inline-add" id="addItemForm">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="add">
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <input type="text" name="item_name" id="itemName" placeholder="Tambah barang, mis. Payung" maxlength="150" required>
      <button type="submit" class="btn btn-primary">Add</button>
    </form>

    <?php if ($suggest): ?>
      <form method="post" action="<?= $action ?>" class="quick-add" id="quickAdd">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="trip_id" value="<?= $tripId ?>">
        <span class="small muted">Quick add:</span>
        <?php foreach ($suggest as $s): ?><button type="submit" name="item_name" value="<?= e($s) ?>" class="chip">+ <?= e($s) ?></button><?php endforeach; ?>
      </form>
    <?php endif; ?>

    <ul class="check-list" id="checkList">
      <?php foreach ($items as $it): ?>
        <li class="check-item<?= $it['is_checked'] ? ' done' : '' ?>" data-id="<?= (int)$it['id'] ?>">
          <form method="post" action="<?= $action ?>" class="toggle-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="toggle">
            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <input type="hidden" name="checked" value="<?= $it['is_checked'] ? 0 : 1 ?>">
            <button type="submit" class="check-btn" aria-label="Tandai selesai"><?= $it['is_checked'] ? '✓' : '' ?></button>
          </form>
          <span class="check-label"><?= e($it['item_name']) ?></span>
          <form method="post" action="<?= $action ?>" class="delete-form">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="delete">
            <input type="hidden" name="trip_id" value="<?= $tripId ?>">
            <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
            <button type="submit" class="btn btn-ghost btn-sm text-danger" aria-label="Hapus">✕</button>
          </form>
        </li>
      <?php endforeach; ?>
    </ul>
    <p class="muted empty-note" id="emptyNote" <?= $items ? 'hidden' : '' ?>>Belum ada item. Tambahkan barang yang perlu dibawa.</p>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
