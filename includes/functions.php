<?php
require_once __DIR__ . '/../config/database.php';

/* ---------- Helper umum ---------- */
function e($v): string { return htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8'); }

function redirect(string $path): void {
    header('Location: ' . BASE_URL . '/' . ltrim($path, '/'));
    exit;
}

function rupiah($n): string { return 'Rp' . number_format((float)$n, 0, ',', '.'); }

// flash('error','pesan') untuk set, flash() untuk ambil sekali pakai
function flash(?string $type = null, ?string $msg = null): ?array {
    if ($type !== null) { $_SESSION['flash'] = ['type' => $type, 'message' => $msg]; return null; }
    $f = $_SESSION['flash'] ?? null;
    unset($_SESSION['flash']);
    return $f;
}

// Simpan input lama agar form tidak kosong saat validasi gagal
function keep_old(array $data): void { $_SESSION['old'] = $data; }
function old(string $key, $default = '') { return $_SESSION['old'][$key] ?? $default; }
function clear_old(): void { unset($_SESSION['old']); }

function valid_date(string $d): bool {
    $dt = DateTime::createFromFormat('Y-m-d', $d);
    return $dt && $dt->format('Y-m-d') === $d;
}

/* ---------- CSRF ---------- */
function csrf_token(): string {
    if (empty($_SESSION['csrf'])) $_SESSION['csrf'] = bin2hex(random_bytes(32));
    return $_SESSION['csrf'];
}
function csrf_field(): string {
    return '<input type="hidden" name="csrf" value="' . csrf_token() . '">';
}
function require_post(): void {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); exit('Method not allowed'); }
    if (!hash_equals($_SESSION['csrf'] ?? '', $_POST['csrf'] ?? '')) { http_response_code(419); exit('Invalid CSRF token'); }
}

/* ---------- Auth ---------- */
function is_logged_in(): bool { return isset($_SESSION['user_id']); }
function user_id(): int { return (int)($_SESSION['user_id'] ?? 0); }

function require_login(): void {
    if (!is_logged_in()) { flash('error', 'Silakan login terlebih dahulu.'); redirect('login.php'); }
}

function generate_username(string $email): string {
    $base = preg_replace('/[^a-z0-9_]/', '', strtolower(strstr($email, '@', true))) ?: 'user';
    $username = $base; $i = 1;
    $st = db()->prepare('SELECT 1 FROM users WHERE username = ?');
    while (true) {
        $st->execute([$username]);
        if (!$st->fetch()) return $username;
        $username = $base . (++$i);
    }
}

/* ---------- Akses trip ---------- */
function require_trip_access(int $tripId): array {
    require_login();
    $st = db()->prepare(
        'SELECT t.*, tm.role FROM trips t
         JOIN trip_members tm ON tm.trip_id = t.id AND tm.user_id = ?
         WHERE t.id = ?'
    );
    $st->execute([user_id(), $tripId]);
    $trip = $st->fetch();
    if (!$trip) { flash('error', 'Trip tidak ditemukan atau Anda bukan anggota.'); redirect('dashboard.php'); }
    return $trip;
}

function require_organizer(array $trip): void {
    if ($trip['role'] !== 'organizer') {
        flash('error', 'Hanya organizer yang dapat melakukan aksi ini.');
        redirect('trip/overview.php?id=' . $trip['id']);
    }
}

function get_trip_members(int $tripId): array {
    $st = db()->prepare(
        'SELECT u.id AS user_id, u.name, u.username, u.email, tm.role
         FROM trip_members tm JOIN users u ON u.id = tm.user_id
         WHERE tm.trip_id = ?
         ORDER BY tm.role = "organizer" DESC, u.name'
    );
    $st->execute([$tripId]);
    return $st->fetchAll();
}

/* ---------- Konstanta kategori (dipakai action & form) ---------- */
const ITINERARY_CATEGORIES = ['Tourism', 'Food', 'Shopping', 'Entertainment', 'Nature', 'Transport', 'Other'];
const PLACE_CATEGORIES     = ['Tourism', 'Food', 'Shopping', 'Entertainment', 'Nature'];
const EXPENSE_CATEGORIES   = ['Accommodation', 'Food', 'Transportation', 'Ticket', 'Shopping', 'Other'];

/* ---------- Statistik trip (dashboard & overview) ---------- */
function trip_status(array $trip): string {
    $today = date('Y-m-d');
    if ($today < $trip['start_date']) return 'upcoming';
    if ($today > $trip['end_date'])   return 'finished';
    return 'active';
}

function get_trip_spent(int $tripId): float {
    $st = db()->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE trip_id = ?');
    $st->execute([$tripId]);
    return (float)$st->fetchColumn();
}

// Semua trip milik/ikutan user + jumlah anggota, total pengeluaran, dan status
function get_user_trips(int $userId): array {
    $st = db()->prepare(
        'SELECT t.*, tm.role,
                (SELECT COUNT(*) FROM trip_members WHERE trip_id = t.id) AS member_count,
                (SELECT COALESCE(SUM(amount),0) FROM expenses WHERE trip_id = t.id) AS spent
         FROM trips t JOIN trip_members tm ON tm.trip_id = t.id
         WHERE tm.user_id = ? ORDER BY t.start_date DESC'
    );
    $st->execute([$userId]);
    $trips = $st->fetchAll();
    foreach ($trips as &$t) $t['status'] = trip_status($t);
    return $trips;
}

function get_dashboard_stats(int $userId): array {
    $trips = get_user_trips($userId);
    $s = ['total' => count($trips), 'active' => 0, 'upcoming' => 0, 'spent' => 0.0];
    foreach ($trips as $t) {
        if ($t['status'] === 'active')   $s['active']++;
        if ($t['status'] === 'upcoming') $s['upcoming']++;
        $s['spent'] += (float)$t['spent'];
    }
    return $s;
}

/* ---------- Helper tampilan ---------- */
// Ambil input lama hanya jika berasal dari form yang tepat (semua key marker ada), lalu hapus
function take_old(array $markers): ?array {
    $o = $_SESSION['old'] ?? null;
    unset($_SESSION['old']);
    if (!is_array($o)) return null;
    foreach ($markers as $m) if (!array_key_exists($m, $o)) return null;
    return $o;
}

function format_date(string $d, string $fmt = 'j F Y'): string { return date($fmt, strtotime($d)); }

function format_date_range(string $start, string $end): string {
    $s = new DateTime($start); $e = new DateTime($end);
    if ($start === $end) return $e->format('j F Y');
    if ($s->format('Y-m') === $e->format('Y-m')) return $s->format('j') . ' - ' . $e->format('j F Y');
    if ($s->format('Y') === $e->format('Y'))     return $s->format('j F') . ' - ' . $e->format('j F Y');
    return $s->format('j F Y') . ' - ' . $e->format('j F Y');
}

// Daftar tanggal (Y-m-d) dari start s/d end
function trip_dates(string $start, string $end): array {
    $out = [];
    $p = new DatePeriod(new DateTime($start), new DateInterval('P1D'), (new DateTime($end))->modify('+1 day'));
    foreach ($p as $d) $out[] = $d->format('Y-m-d');
    return $out;
}

function signed_rupiah($n): string {
    $n = (float)$n;
    return ($n > 0 ? '+' : ($n < 0 ? '-' : '')) . rupiah(abs($n));
}

// Angka untuk value input number (tanpa nol di belakang)
function num_input($n): string {
    if ($n === '' || $n === null) return '';
    return rtrim(rtrim(number_format((float)$n, 2, '.', ''), '0'), '.');
}

function initials(string $name): string {
    $parts = preg_split('/\s+/', trim($name));
    $s = mb_substr($parts[0] ?? '', 0, 1);
    if (count($parts) > 1) $s .= mb_substr(end($parts), 0, 1);
    return mb_strtoupper($s);
}

function status_label(string $s): string {
    return ['upcoming' => 'Upcoming', 'active' => 'Ongoing', 'finished' => 'Finished'][$s] ?? $s;
}

function category_icon(string $c): string {
    return [
        'Tourism' => '🏛️', 'Food' => '🍜', 'Shopping' => '🛍️', 'Entertainment' => '🎭', 'Nature' => '🌿',
        'Transport' => '🚌', 'Other' => '📌', 'Accommodation' => '🏨', 'Transportation' => '🚗', 'Ticket' => '🎟️',
    ][$c] ?? '📌';
}

function category_badge(string $c): string {
    return '<span class="badge cat-' . e(strtolower($c)) . '">' . category_icon($c) . ' ' . e($c) . '</span>';
}
