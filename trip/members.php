<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];
$members = get_trip_members($tripId);
$isOrganizer = $trip['role'] === 'organizer';

$pageTitle = 'Members · ' . $trip['name'];
$activeTab = 'members';
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
?>
<div class="grid-main">
  <section class="card">
    <h3>Trip Members</h3>
    <ul class="member-list">
      <?php foreach ($members as $m): ?>
        <li class="member-item">
          <span class="avatar"><?= e(initials($m['name'])) ?></span>
          <div class="grow">
            <strong><?= e($m['name']) ?></strong>
            <div class="small muted">@<?= e($m['username']) ?> · <?= e($m['email']) ?></div>
          </div>
          <span class="badge <?= $m['role'] === 'organizer' ? 'badge-active' : '' ?>"><?= $m['role'] === 'organizer' ? 'Organizer' : 'Member' ?></span>
          <?php if ($isOrganizer && $m['role'] !== 'organizer'): ?>
            <form method="post" action="<?= BASE_URL ?>/actions/member.php" class="inline-form" onsubmit="return confirm('Hapus anggota ini dari trip?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="remove">
              <input type="hidden" name="trip_id" value="<?= $tripId ?>">
              <input type="hidden" name="user_id" value="<?= (int)$m['user_id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm text-danger">Remove</button>
            </form>
          <?php endif; ?>
        </li>
      <?php endforeach; ?>
    </ul>
  </section>

  <aside class="card form-card">
    <h3>Add Member</h3>
    <?php if ($isOrganizer): ?>
      <p class="muted small">Teman harus sudah punya akun TripMate. Masukkan email atau username-nya.</p>
      <form method="post" action="<?= BASE_URL ?>/actions/member.php" class="form">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="add">
        <input type="hidden" name="trip_id" value="<?= $tripId ?>">
        <div class="field"><label for="identifier">Email or Username</label>
          <input type="text" id="identifier" name="identifier" placeholder="andi@email.com / andi" required></div>
        <button type="submit" class="btn btn-primary btn-block">Add Member</button>
      </form>
    <?php else: ?>
      <p class="muted">Hanya organizer yang dapat menambahkan atau menghapus anggota.</p>
    <?php endif; ?>
  </aside>
</div>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
