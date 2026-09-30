<?php
require_once __DIR__ . '/../includes/functions.php';
require_login();
$old = take_old(['destination']);
$f = $old ?: ['name' => '', 'destination' => '', 'start_date' => '', 'end_date' => '', 'budget' => '', 'description' => ''];
$formAction = 'create';
$submitLabel = 'Create Trip';
$pageTitle = 'Create Trip';
$pageScripts = ['trip-form.js'];
require_once __DIR__ . '/../includes/header.php';
?>
<div class="narrow">
  <a href="<?= BASE_URL ?>/dashboard.php" class="back-link">← My Trips</a>
  <h1>Create New Trip</h1>
  <p class="muted">Kamu otomatis menjadi Organizer dan bisa menambahkan anggota setelahnya.</p>
  <div class="card"><?php require __DIR__ . '/../includes/trip_form.php'; ?></div>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
