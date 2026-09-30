<?php
// Butuh: $trip (dengan 'role'), $activeTab
$tabs = [
    'overview'   => 'Overview',
    'itinerary'  => 'Itinerary',
    'places'     => 'Places',
    'expenses'   => 'Expenses',
    'settlement' => 'Split Bill',
    'checklist'  => 'Checklist',
    'members'    => 'Members',
];
$activeTab = $activeTab ?? 'overview';
$tid = (int)$trip['id'];
$tstatus = trip_status($trip);
?>
<section class="trip-head">
  <a href="<?= BASE_URL ?>/dashboard.php" class="back-link">← My Trips</a>
  <div class="trip-head-main">
    <div>
      <h1><?= e($trip['name']) ?></h1>
      <p class="muted">📍 <?= e($trip['destination']) ?> &nbsp;·&nbsp; 📅 <?= e(format_date_range($trip['start_date'], $trip['end_date'])) ?></p>
    </div>
    <div class="trip-head-actions">
      <span class="badge badge-<?= e($tstatus) ?>"><?= e(status_label($tstatus)) ?></span>
      <?php if ($trip['role'] === 'organizer'): ?>
        <a href="<?= BASE_URL ?>/trip/edit.php?id=<?= $tid ?>" class="btn btn-outline btn-sm">Edit Trip</a>
      <?php endif; ?>
    </div>
  </div>
  <nav class="tabs" aria-label="Trip navigation">
    <?php foreach ($tabs as $key => $label): ?>
      <a href="<?= BASE_URL ?>/trip/<?= $key ?>.php?id=<?= $tid ?>" class="tab<?= $key === $activeTab ? ' active' : '' ?>"><?= e($label) ?></a>
    <?php endforeach; ?>
  </nav>
</section>
