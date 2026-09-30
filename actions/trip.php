<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();
require_login();

$action = $_POST['action'] ?? '';

function read_trip_input(): array {
    return [
        'name'        => trim($_POST['name'] ?? ''),
        'destination' => trim($_POST['destination'] ?? ''),
        'start_date'  => $_POST['start_date'] ?? '',
        'end_date'    => $_POST['end_date'] ?? '',
        'budget'      => (float)($_POST['budget'] ?? 0),
        'description' => trim($_POST['description'] ?? ''),
    ];
}

function validate_trip(array $d): ?string {
    if ($d['name'] === '' || $d['destination'] === '') return 'Nama perjalanan dan destinasi wajib diisi.';
    if (!valid_date($d['start_date']) || !valid_date($d['end_date'])) return 'Tanggal tidak valid.';
    if ($d['end_date'] < $d['start_date']) return 'Tanggal selesai tidak boleh sebelum tanggal mulai.';
    if ($d['budget'] < 0) return 'Budget tidak boleh negatif.';
    return null;
}

if ($action === 'create') {
    $d = read_trip_input();
    keep_old($d);
    if ($err = validate_trip($d)) { flash('error', $err); redirect('trip/create.php'); }

    $pdo = db();
    $pdo->beginTransaction();
    try {
        $pdo->prepare('INSERT INTO trips (owner_id,name,destination,start_date,end_date,budget,description) VALUES (?,?,?,?,?,?,?)')
            ->execute([user_id(), $d['name'], $d['destination'], $d['start_date'], $d['end_date'], $d['budget'], $d['description']]);
        $tripId = (int)$pdo->lastInsertId();
        $pdo->prepare('INSERT INTO trip_members (trip_id,user_id,role) VALUES (?,?,"organizer")')
            ->execute([$tripId, user_id()]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        flash('error', 'Gagal menyimpan trip.');
        redirect('trip/create.php');
    }
    clear_old();
    flash('success', 'Trip berhasil dibuat.');
    redirect('trip/overview.php?id=' . $tripId);
}

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip = require_trip_access($tripId);
require_organizer($trip);

if ($action === 'update') {
    $d = read_trip_input();
    if ($err = validate_trip($d)) { flash('error', $err); redirect('trip/edit.php?id=' . $tripId); }
    db()->prepare('UPDATE trips SET name=?,destination=?,start_date=?,end_date=?,budget=?,description=? WHERE id=?')
        ->execute([$d['name'], $d['destination'], $d['start_date'], $d['end_date'], $d['budget'], $d['description'], $tripId]);
    flash('success', 'Trip berhasil diperbarui.');
    redirect('trip/overview.php?id=' . $tripId);
}

if ($action === 'delete') {
    db()->prepare('DELETE FROM trips WHERE id = ?')->execute([$tripId]); // child terhapus via ON DELETE CASCADE
    flash('success', 'Trip dihapus.');
    redirect('dashboard.php');
}

http_response_code(400);
exit('Aksi tidak dikenal.');
