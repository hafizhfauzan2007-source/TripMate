<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip = require_trip_access($tripId);
require_organizer($trip);
$back = 'trip/members.php?id=' . $tripId;
$action = $_POST['action'] ?? '';

if ($action === 'add') {
    $ident = strtolower(trim($_POST['identifier'] ?? '')); // email atau username
    if ($ident === '') { flash('error', 'Isi email atau username.'); redirect($back); }

    $st = db()->prepare('SELECT id, name FROM users WHERE email = ? OR username = ?');
    $st->execute([$ident, $ident]);
    $u = $st->fetch();
    if (!$u) { flash('error', 'Pengguna tidak ditemukan.'); redirect($back); }

    $st = db()->prepare('SELECT 1 FROM trip_members WHERE trip_id = ? AND user_id = ?');
    $st->execute([$tripId, $u['id']]);
    if ($st->fetch()) { flash('error', $u['name'] . ' sudah menjadi anggota.'); redirect($back); }

    db()->prepare('INSERT INTO trip_members (trip_id,user_id,role) VALUES (?,?,"member")')->execute([$tripId, $u['id']]);
    flash('success', $u['name'] . ' berhasil ditambahkan.');
    redirect($back);
}

if ($action === 'remove') {
    $uid = (int)($_POST['user_id'] ?? 0);
    if ($uid === (int)$trip['owner_id']) { flash('error', 'Organizer tidak dapat dihapus.'); redirect($back); }

    // Cegah hapus anggota yang sudah terlibat pengeluaran (menjaga integritas settlement)
    $st = db()->prepare(
        'SELECT 1 FROM expenses e LEFT JOIN expense_splits s ON s.expense_id = e.id
         WHERE e.trip_id = ? AND (e.paid_by = ? OR s.user_id = ?) LIMIT 1'
    );
    $st->execute([$tripId, $uid, $uid]);
    if ($st->fetch()) { flash('error', 'Anggota ini sudah terlibat dalam pengeluaran, tidak bisa dihapus.'); redirect($back); }

    db()->prepare('DELETE FROM trip_members WHERE trip_id = ? AND user_id = ?')->execute([$tripId, $uid]);
    flash('success', 'Anggota dihapus.');
    redirect($back);
}

http_response_code(400);
exit('Aksi tidak dikenal.');
