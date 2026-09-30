<?php
// Butuh: $f (array nilai form), $formAction ('create'|'update'), $submitLabel
?>
<form method="post" action="<?= BASE_URL ?>/actions/trip.php" class="form" id="tripForm" data-validate="trip">
  <?= csrf_field() ?>
  <input type="hidden" name="action" value="<?= e($formAction) ?>">
  <?php if (!empty($f['id'])): ?><input type="hidden" name="trip_id" value="<?= (int)$f['id'] ?>"><?php endif; ?>

  <div class="field">
    <label for="name">Trip Name</label>
    <input type="text" id="name" name="name" value="<?= e($f['name']) ?>" placeholder="mis. Jogja Trip 2026" maxlength="150" required>
  </div>
  <div class="field">
    <label for="destination">Destination</label>
    <input type="text" id="destination" name="destination" value="<?= e($f['destination']) ?>" placeholder="mis. Yogyakarta" maxlength="150" required>
  </div>
  <div class="form-grid">
    <div class="field">
      <label for="start_date">Start Date</label>
      <input type="date" id="start_date" name="start_date" value="<?= e($f['start_date']) ?>" required>
    </div>
    <div class="field">
      <label for="end_date">End Date</label>
      <input type="date" id="end_date" name="end_date" value="<?= e($f['end_date']) ?>" required>
    </div>
  </div>
  <div class="field">
    <label for="budget">Budget (Rp)</label>
    <input type="number" id="budget" name="budget" value="<?= e(num_input($f['budget'])) ?>" min="0" step="1000" placeholder="2000000">
  </div>
  <div class="field">
    <label for="description">Description</label>
    <textarea id="description" name="description" rows="4" placeholder="Catatan singkat tentang perjalanan ini"><?= e($f['description']) ?></textarea>
  </div>
  <p class="field-error" id="tripError" hidden></p>
  <button type="submit" class="btn btn-primary btn-lg"><?= e($submitLabel) ?></button>
</form>
