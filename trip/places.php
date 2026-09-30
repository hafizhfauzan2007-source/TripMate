<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];
$pdo = db();
$days = trip_dates($trip['start_date'], $trip['end_date']);

$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM places WHERE id = ? AND trip_id = ?');
    $st->execute([$editId, $tripId]);
    $editing = $st->fetch() ?: null;
    if (!$editing) $editId = 0;
}
$f = ['name' => '', 'location' => '', 'category' => 'Tourism', 'description' => '', 'estimated_cost' => '', 'notes' => ''];
$old = take_old(['description', 'estimated_cost']);
if ($old)         $f = array_merge($f, $old);
elseif ($editing) $f = array_merge($f, $editing);

$st = $pdo->prepare('SELECT * FROM places WHERE trip_id = ? ORDER BY id DESC');
$st->execute([$tripId]);
$places = $st->fetchAll();

$pageTitle = 'Places · ' . $trip['name'];
$activeTab = 'places';
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
$self = BASE_URL . '/trip/places.php?id=' . $tripId;
?>
<details class="card form-card" id="placeForm" <?= ($editId || $old) ? 'open' : '' ?>>
  <summary class="form-summary"><?= $editId ? '✏️ Edit Place' : '＋ Add Place' ?></summary>
  <form method="post" action="<?= BASE_URL ?>/actions/place.php" class="form" data-validate="place">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="hidden" name="id" value="<?= $editId ?>">
    <div class="form-grid">
      <div class="field"><label for="name">Place Name</label>
        <input type="text" id="name" name="name" value="<?= e($f['name']) ?>" maxlength="150" required></div>
      <div class="field"><label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($f['location']) ?>" maxlength="150"></div>
      <div class="field"><label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach (PLACE_CATEGORIES as $c): ?><option value="<?= $c ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= category_icon($c) . ' ' . $c ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="estimated_cost">Est. Cost (Rp)</label>
        <input type="number" id="estimated_cost" name="estimated_cost" value="<?= e(num_input($f['estimated_cost'])) ?>" min="0" step="1000"></div>
    </div>
    <div class="field"><label for="description">Description</label>
      <textarea id="description" name="description" rows="2"><?= e($f['description']) ?></textarea></div>
    <div class="field"><label for="notes">Notes</label>
      <textarea id="notes" name="notes" rows="2"><?= e($f['notes']) ?></textarea></div>
    <p class="field-error" hidden></p>
    <div class="row-actions">
      <button type="submit" class="btn btn-primary"><?= $editId ? 'Save Changes' : 'Save Place' ?></button>
      <?php if ($editId): ?><a href="<?= $self ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</details>

<?php if (!$places): ?>
  <div class="card empty-state"><div class="empty-icon">📍</div><h3>Belum ada tempat tersimpan</h3><p class="muted">Simpan tempat yang ingin dikunjungi, lalu tambahkan ke itinerary.</p></div>
<?php else: ?>
<div class="grid-cards">
  <?php foreach ($places as $p): ?>
    <article class="card place-card">
      <div class="row-between"><?= category_badge($p['category']) ?><span class="small muted">Est. <?= e(rupiah($p['estimated_cost'])) ?></span></div>
      <h3><?= e($p['name']) ?></h3>
      <?php if ($p['location']): ?><p class="small muted">📍 <?= e($p['location']) ?></p><?php endif; ?>
      <?php if ($p['description']): ?><p><?= nl2br(e($p['description'])) ?></p><?php endif; ?>
      <?php if ($p['notes']): ?><p class="small muted">📝 <?= nl2br(e($p['notes'])) ?></p><?php endif; ?>

      <details class="mini-form">
        <summary class="btn btn-primary btn-sm">Add to Itinerary</summary>
        <form method="post" action="<?= BASE_URL ?>/actions/place.php" class="form">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="to_itinerary">
          <input type="hidden" name="trip_id" value="<?= $tripId ?>">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <div class="form-grid">
            <div class="field"><label>Day</label>
              <select name="day_date"><?php foreach ($days as $i => $d): ?><option value="<?= $d ?>">Day <?= $i + 1 ?> · <?= e(format_date($d, 'j M')) ?></option><?php endforeach; ?></select></div>
            <div class="field"><label>Time</label><input type="time" name="start_time" value="09:00" required></div>
          </div>
          <button type="submit" class="btn btn-primary btn-sm">Add</button>
        </form>
      </details>

      <div class="row-actions card-actions">
        <a href="<?= $self ?>&edit=<?= (int)$p['id'] ?>#placeForm" class="btn btn-ghost btn-sm">Edit</a>
        <form method="post" action="<?= BASE_URL ?>/actions/place.php" class="inline-form" onsubmit="return confirm('Hapus tempat ini?');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="delete">
          <input type="hidden" name="trip_id" value="<?= $tripId ?>">
          <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
          <button type="submit" class="btn btn-ghost btn-sm text-danger">Delete</button>
        </form>
      </div>
    </article>
  <?php endforeach; ?>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
