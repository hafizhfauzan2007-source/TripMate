<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
require_organizer($trip);
$old = take_old(['destination']);
$f = $old ?: $trip;
$f['id'] = $trip['id'];
$formAction = 'update';
$submitLabel = 'Save Changes';
$pageTitle = 'Edit ' . $trip['name'];
$pageScripts = ['trip-form.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="narrow">
  <a href="<?= BASE_URL ?>/trip/overview.php?id=<?= (int)$trip['id'] ?>" class="back-link">← Back to trip</a>
  <h1>Edit Trip</h1>
  <div class="card"><?php require __DIR__ . '/../includes/trip_form.php'; ?></div>

  <div class="card danger-zone">
    <h3>Danger zone</h3>
    <p class="muted">Menghapus trip akan menghapus seluruh itinerary, tempat, pengeluaran, dan checklist di dalamnya.</p>
    <form method="post" action="<?= BASE_URL ?>/actions/trip.php" onsubmit="return confirm('Hapus trip ini beserta seluruh datanya? Tindakan ini tidak bisa dibatalkan.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <input type="hidden" name="trip_id" value="<?= (int)$trip['id'] ?>">
      <button type="submit" class="btn btn-danger">Delete Trip</button>
    </form>
  </div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
