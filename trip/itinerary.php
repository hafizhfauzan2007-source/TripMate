<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];
$pdo = db();

$days = trip_dates($trip['start_date'], $trip['end_date']);
$filter = $_GET['date'] ?? '';
if (!in_array($filter, $days, true)) $filter = '';

// Mode edit
$editId = (int)($_GET['edit'] ?? 0);
$editing = null;
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM itineraries WHERE id = ? AND trip_id = ?');
    $st->execute([$editId, $tripId]);
    $editing = $st->fetch() ?: null;
    if (!$editing) $editId = 0;
}

$f = ['day_date' => $filter ?: $trip['start_date'], 'start_time' => '08:00', 'activity' => '', 'location' => '',
      'category' => 'Tourism', 'estimated_cost' => '', 'notes' => ''];
$old = take_old(['activity']);
if ($old)          $f = array_merge($f, $old);
elseif ($editing)  { $f = array_merge($f, $editing); $f['start_time'] = substr($editing['start_time'], 0, 5); }

// Ambil & kelompokkan per tanggal
$st = $pdo->prepare('SELECT * FROM itineraries WHERE trip_id = ? ORDER BY day_date, start_time');
$st->execute([$tripId]);
$grouped = [];
foreach ($st as $row) $grouped[$row['day_date']][] = $row;

$pageTitle = 'Itinerary · ' . $trip['name'];
$activeTab = 'itinerary';
$pageScripts = ['itinerary.js'];
$jsData = ['tripId' => $tripId, 'start' => $trip['start_date'], 'end' => $trip['end_date']];
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
$self = BASE_URL . '/trip/itinerary.php?id=' . $tripId;
?>
<div class="grid-main">
  <section>
    <div class="chips" id="dayFilter" aria-label="Filter by day">
      <a href="<?= $self ?>" class="chip<?= $filter === '' ? ' active' : '' ?>" data-filter="all">All days</a>
      <?php foreach ($days as $i => $d): ?>
        <a href="<?= $self ?>&date=<?= $d ?>" class="chip<?= $filter === $d ? ' active' : '' ?>" data-filter="<?= $d ?>">Day <?= $i + 1 ?> · <?= e(format_date($d, 'j M')) ?></a>
      <?php endforeach; ?>
    </div>

    <div id="itineraryList">
    <?php if (!$grouped): ?>
      <div class="card empty-state"><div class="empty-icon">🗓️</div><h3>Belum ada itinerary</h3><p class="muted">Tambahkan aktivitas pertama lewat form di samping.</p></div>
    <?php endif; ?>

    <?php foreach ($grouped as $date => $rows):
        $idx = array_search($date, $days, true);
        $dayTotal = array_sum(array_column($rows, 'estimated_cost')); ?>
      <section class="day-group" data-date="<?= e($date) ?>" <?= ($filter && $filter !== $date) ? 'hidden' : '' ?>>
        <div class="day-title">
          <h2><?= $idx !== false ? 'DAY ' . ($idx + 1) . ' — ' : '' ?><?= e(format_date($date, 'j F')) ?></h2>
          <span class="small muted">Est. <?= e(rupiah($dayTotal)) ?></span>
        </div>
        <?php foreach ($rows as $it): ?>
          <article class="tl-item">
            <div class="tl-time"><?= e(substr($it['start_time'], 0, 5)) ?></div>
            <div class="card tl-body">
              <div class="tl-top">
                <h4><?= e($it['activity']) ?></h4>
                <?= category_badge($it['category']) ?>
              </div>
              <?php if ($it['location']): ?><p class="small muted">📍 <?= e($it['location']) ?></p><?php endif; ?>
              <?php if ($it['notes']): ?><p class="small"><?= nl2br(e($it['notes'])) ?></p><?php endif; ?>
              <div class="tl-foot">
                <span class="small muted">Est. <?= e(rupiah($it['estimated_cost'])) ?></span>
                <div class="row-actions">
                  <a href="<?= $self ?>&edit=<?= (int)$it['id'] ?>#itineraryForm" class="btn btn-ghost btn-sm">Edit</a>
                  <form method="post" action="<?= BASE_URL ?>/actions/itinerary.php" class="inline-form" onsubmit="return confirm('Hapus aktivitas ini?');">
                    <?= csrf_field() ?>
                    <input type="hidden" name="action" value="delete">
                    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
                    <input type="hidden" name="id" value="<?= (int)$it['id'] ?>">
                    <button type="submit" class="btn btn-ghost btn-sm text-danger">Delete</button>
                  </form>
                </div>
              </div>
            </div>
          </article>
        <?php endforeach; ?>
      </section>
    <?php endforeach; ?>
    </div>
  </section>

  <aside class="card form-card sticky" id="itineraryForm">
    <h3><?= $editId ? 'Edit Activity' : 'Add Activity' ?></h3>
    <form method="post" action="<?= BASE_URL ?>/actions/itinerary.php" class="form" data-validate="itinerary">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="save">
      <input type="hidden" name="trip_id" value="<?= $tripId ?>">
      <input type="hidden" name="id" value="<?= $editId ?>">
      <div class="form-grid">
        <div class="field">
          <label for="day_date">Date</label>
          <input type="date" id="day_date" name="day_date" value="<?= e($f['day_date']) ?>" min="<?= e($trip['start_date']) ?>" max="<?= e($trip['end_date']) ?>" required>
        </div>
        <div class="field">
          <label for="start_time">Time</label>
          <input type="time" id="start_time" name="start_time" value="<?= e($f['start_time']) ?>" required>
        </div>
      </div>
      <div class="field"><label for="activity">Activity</label>
        <input type="text" id="activity" name="activity" value="<?= e($f['activity']) ?>" placeholder="mis. Malioboro" maxlength="150" required></div>
      <div class="field"><label for="location">Location</label>
        <input type="text" id="location" name="location" value="<?= e($f['location']) ?>" maxlength="150"></div>
      <div class="form-grid">
        <div class="field"><label for="category">Category</label>
          <select id="category" name="category">
            <?php foreach (ITINERARY_CATEGORIES as $c): ?><option value="<?= $c ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= category_icon($c) . ' ' . $c ?></option><?php endforeach; ?>
          </select></div>
        <div class="field"><label for="estimated_cost">Est. Cost (Rp)</label>
          <input type="number" id="estimated_cost" name="estimated_cost" value="<?= e(num_input($f['estimated_cost'])) ?>" min="0" step="1000"></div>
      </div>
      <div class="field"><label for="notes">Notes</label>
        <textarea id="notes" name="notes" rows="3"><?= e($f['notes']) ?></textarea></div>
      <p class="field-error" hidden></p>
      <button type="submit" class="btn btn-primary btn-block"><?= $editId ? 'Save Changes' : 'Add Activity' ?></button>
      <?php if ($editId): ?><a href="<?= $self ?>" class="btn btn-ghost btn-block">Cancel</a><?php endif; ?>
    </form>
  </aside>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
