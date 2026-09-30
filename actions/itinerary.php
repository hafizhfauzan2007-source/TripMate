<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip   = require_trip_access($tripId);   // semua anggota boleh mengelola itinerary
$action = $_POST['action'] ?? '';
$back   = 'trip/itinerary.php?id=' . $tripId;

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $d = [
        'day_date'       => $_POST['day_date'] ?? '',
        'start_time'     => $_POST['start_time'] ?? '',
        'activity'       => trim($_POST['activity'] ?? ''),
        'location'       => trim($_POST['location'] ?? ''),
        'category'       => $_POST['category'] ?? 'Other',
        'estimated_cost' => (float)($_POST['estimated_cost'] ?? 0),
        'notes'          => trim($_POST['notes'] ?? ''),
    ];
    keep_old($d);

    $err = null;
    if ($d['activity'] === '')                                             $err = 'Nama aktivitas wajib diisi.';
    elseif (!valid_date($d['day_date']))                                   $err = 'Tanggal tidak valid.';
    elseif ($d['day_date'] < $trip['start_date'] || $d['day_date'] > $trip['end_date'])
                                                                           $err = 'Tanggal harus berada dalam rentang perjalanan.';
    elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $d['start_time']))  $err = 'Format waktu tidak valid (HH:MM).';
    elseif (!in_array($d['category'], ITINERARY_CATEGORIES, true))         $err = 'Kategori tidak valid.';
    elseif ($d['estimated_cost'] < 0)                                      $err = 'Estimasi biaya tidak boleh negatif.';

    if ($err) { flash('error', $err); redirect($back . ($id ? '&edit=' . $id : '')); }

    if ($id) {
        $st = db()->prepare('SELECT 1 FROM itineraries WHERE id = ? AND trip_id = ?');
        $st->execute([$id, $tripId]);
        if (!$st->fetch()) { flash('error', 'Itinerary tidak ditemukan.'); redirect($back); }

        db()->prepare('UPDATE itineraries SET day_date=?,start_time=?,activity=?,location=?,category=?,estimated_cost=?,notes=? WHERE id=? AND trip_id=?')
            ->execute([$d['day_date'], $d['start_time'], $d['activity'], $d['location'], $d['category'], $d['estimated_cost'], $d['notes'], $id, $tripId]);
        flash('success', 'Itinerary diperbarui.');
    } else {
        db()->prepare('INSERT INTO itineraries (trip_id,day_date,start_time,activity,location,category,estimated_cost,notes) VALUES (?,?,?,?,?,?,?,?)')
            ->execute([$tripId, $d['day_date'], $d['start_time'], $d['activity'], $d['location'], $d['category'], $d['estimated_cost'], $d['notes']]);
        flash('success', 'Itinerary ditambahkan.');
    }
    clear_old();
    redirect($back);
}

if ($action === 'delete') {
    $id = (int)($_POST['id'] ?? 0);
    db()->prepare('DELETE FROM itineraries WHERE id = ? AND trip_id = ?')->execute([$id, $tripId]);
    flash('success', 'Itinerary dihapus.');
    redirect($back);
}

http_response_code(400);
exit('Aksi tidak dikenal.');
