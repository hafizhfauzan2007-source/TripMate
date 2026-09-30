<?php
require_once __DIR__ . '/includes/functions.php';
$pageTitle = 'Plan Your Trip';
$mainClass = '';
require_once __DIR__ . '/includes/header.php';

$features = [
    ['🗓️', 'Itinerary', 'Susun jadwal per hari dan jam lengkap dengan lokasi, kategori, dan estimasi biaya.'],
    ['📍', 'Places', 'Simpan tempat yang ingin dikunjungi, lalu masukkan ke itinerary dengan satu klik.'],
    ['💸', 'Expenses', 'Catat setiap pengeluaran, siapa yang membayar, dan siapa yang ikut menanggung.'],
    ['🧾', 'Split Bill', 'Bagi rata atau custom. Hutang-piutang antaranggota dihitung otomatis.'],
    ['🎒', 'Checklist', 'Daftar barang bawaan dengan progress yang terhitung otomatis.'],
    ['👥', 'Members', 'Ajak teman ke dalam perjalanan lewat email atau username.'],
];
$steps = [
    ['1', 'Create a trip', 'Isi nama, destinasi, tanggal, dan budget perjalanan.'],
    ['2', 'Invite your friends', 'Tambahkan anggota agar semua bisa ikut mencatat.'],
    ['3', 'Plan & track', 'Susun itinerary, catat pengeluaran, dan lihat hasil split bill.'],
];
?>
<section class="hero">
  <div class="container hero-grid">
    <div class="hero-copy">
      <span class="eyebrow">Travel Planner + Split Bill</span>
      <h1>Plan Your Trip, Share the Journey, Split the Cost.</h1>
      <p class="lead">TripMate membantu kamu dan teman-teman merencanakan perjalanan, mencatat pengeluaran bersama, dan menghitung siapa harus membayar ke siapa tanpa ribet.</p>
      <div class="hero-actions">
        <a href="<?= BASE_URL ?>/trip/create.php" class="btn btn-primary btn-lg">Create Trip</a>
        <?php if (is_logged_in()): ?>
          <a href="<?= BASE_URL ?>/dashboard.php" class="btn btn-outline btn-lg">Dashboard</a>
        <?php else: ?>
          <a href="<?= BASE_URL ?>/login.php" class="btn btn-outline btn-lg">Login</a>
        <?php endif; ?>
      </div>
    </div>

    <!-- Contoh tampilan trip (statis) -->
    <div class="hero-card card" aria-hidden="true">
      <p class="eyebrow">Sample trip</p>
      <h3>Jogja Trip 2026</h3>
      <p class="muted">📅 10 - 13 October 2026 · 👥 3 members</p>
      <div class="progress"><div class="progress-bar" style="width:68%"></div></div>
      <div class="row-between small"><span>Spent <strong>Rp1.350.000</strong></span><span class="muted">of Rp2.000.000</span></div>
      <hr>
      <p class="small muted">Settlement</p>
      <div class="settle-item"><span>Andi → Hafizh</span><strong>Rp100.000</strong></div>
      <div class="settle-item"><span>Budi → Hafizh</span><strong>Rp190.000</strong></div>
    </div>
  </div>
</section>

<section class="section container" id="features">
  <div class="section-head">
    <h2>Everything for your trip, in one place</h2>
    <p class="muted">Fokus pada perencanaan dan keuangan perjalanan bersama.</p>
  </div>
  <div class="grid-3">
    <?php foreach ($features as [$icon, $title, $desc]): ?>
      <div class="card feature-card">
        <div class="feature-icon"><?= $icon ?></div>
        <h3><?= e($title) ?></h3>
        <p class="muted"><?= e($desc) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="section container">
  <div class="section-head"><h2>How it works</h2></div>
  <div class="grid-3">
    <?php foreach ($steps as [$n, $title, $desc]): ?>
      <div class="step">
        <span class="step-num"><?= $n ?></span>
        <h3><?= e($title) ?></h3>
        <p class="muted"><?= e($desc) ?></p>
      </div>
    <?php endforeach; ?>
  </div>
</section>

<section class="container section">
  <div class="cta card">
    <h2>Ready for your next trip?</h2>
    <p>Mulai rencanakan perjalananmu sekarang.</p>
    <a href="<?= BASE_URL ?>/<?= is_logged_in() ? 'trip/create.php' : 'register.php' ?>" class="btn btn-primary btn-lg"><?= is_logged_in() ? 'Create Trip' : 'Get Started' ?></a>
  </div>
</section>
<?php require_once __DIR__ . '/includes/footer.php'; ?>
