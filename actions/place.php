<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip   = require_trip_access($tripId);
$action = $_POST['action'] ?? '';
$back   = 'trip/places.php?id=' . $tripId;

function find_place(int $id, int $tripId): ?array {
    $st = db()->prepare('SELECT * FROM places WHERE id = ? AND trip_id = ?');
    $st->execute([$id, $tripId]);
    return $st->fetch() ?: null;
}

if ($action === 'save') {
    $id = (int)($_POST['id'] ?? 0);
    $d = [
        'name'           => trim($_POST['name'] ?? ''),
        'location'       => trim($_POST['location'] ?? ''),
        'category'       => $_POST['category'] ?? 'Tourism',
        'description'    => trim($_POST['description'] ?? ''),
        'estimated_cost' => (float)($_POST['estimated_cost'] ?? 0),
        'notes'          => trim($_POST['notes'] ?? ''),
    ];
    keep_old($d);

    $err = null;
    if ($d['name'] === '')                                        $err = 'Nama tempat wajib diisi.';
    elseif (!in_array($d['category'], PLACE_CATEGORIES, true))    $err = 'Kategori tidak valid.';
    elseif ($d['estimated_cost'] < 0)                             $err = 'Estimasi biaya tidak boleh negatif.';
    if ($err) { flash('error', $err); redirect($back . ($id ? '&edit=' . $id : '')); }

    if ($id) {
        if (!find_place($id, $tripId)) { flash('error', 'Tempat tidak ditemukan.'); redirect($back); }
        db()->prepare('UPDATE places SET name=?,location=?,category=?,description=?,estimated_cost=?,notes=? WHERE id=? AND trip_id=?')
            ->execute([$d['name'], $d['location'], $d['category'], $d['description'], $d['estimated_cost'], $d['notes'], $id, $tripId]);
        flash('success', 'Tempat diperbarui.');
    } else {
        db()->prepare('INSERT INTO places (trip_id,name,location,category,description,estimated_cost,notes) VALUES (?,?,?,?,?,?,?)')
            ->execute([$tripId, $d['name'], $d['location'], $d['category'], $d['description'], $d['estimated_cost'], $d['notes']]);
        flash('success', 'Tempat disimpan.');
    }
    clear_old();
    redirect($back);
}

if ($action === 'delete') {
    db()->prepare('DELETE FROM places WHERE id = ? AND trip_id = ?')->execute([(int)($_POST['id'] ?? 0), $tripId]);
    flash('success', 'Tempat dihapus.');
    redirect($back);
}

// Tombol "Add to Itinerary": salin data place menjadi baris itinerary
if ($action === 'to_itinerary') {
    $place = find_place((int)($_POST['id'] ?? 0), $tripId);
    $date  = $_POST['day_date'] ?? '';
    $time  = $_POST['start_time'] ?? '';

    if (!$place)                                                            $err = 'Tempat tidak ditemukan.';
    elseif (!valid_date($date) || $date < $trip['start_date'] || $date > $trip['end_date'])
                                                                            $err = 'Pilih tanggal dalam rentang perjalanan.';
    elseif (!preg_match('/^([01]\d|2[0-3]):[0-5]\d$/', $time))              $err = 'Format waktu tidak valid (HH:MM).';
    else $err = null;
    if ($err) { flash('error', $err); redirect($back); }

    db()->prepare('INSERT INTO itineraries (trip_id,day_date,start_time,activity,location,category,estimated_cost,notes) VALUES (?,?,?,?,?,?,?,?)')
        ->execute([$tripId, $date, $time, $place['name'], $place['location'], $place['category'], $place['estimated_cost'], $place['notes']]);
    flash('success', '"' . $place['name'] . '" ditambahkan ke itinerary.');
    redirect('trip/itinerary.php?id=' . $tripId . '&date=' . $date);
}

http_response_code(400);
exit('Aksi tidak dikenal.');
