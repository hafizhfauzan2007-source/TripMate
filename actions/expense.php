<?php
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/settlement.php';
require_post();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip   = require_trip_access($tripId);
$action = $_POST['action'] ?? '';
$back   = 'trip/expenses.php?id=' . $tripId;

if ($action === 'save') {
    $id        = (int)($_POST['id'] ?? 0);
    $memberIds = array_map(fn($m) => (int)$m['user_id'], get_trip_members($tripId));

    $d = [
        'name'         => trim($_POST['name'] ?? ''),
        'amount'       => (float)($_POST['amount'] ?? 0),
        'expense_date' => $_POST['expense_date'] ?? '',
        'category'     => $_POST['category'] ?? 'Other',
        'paid_by'      => (int)($_POST['paid_by'] ?? 0),
        'split_type'   => $_POST['split_type'] ?? 'equal',
        'notes'        => trim($_POST['notes'] ?? ''),
    ];
    $splitUsers = array_map('intval', (array)($_POST['split_users'] ?? []));
    $custom     = (array)($_POST['custom'] ?? []);   // custom[user_id] = nominal
    keep_old(array_diff_key($_POST, ['csrf' => 1]));

    $err = null; $splits = [];
    if ($d['name'] === '')                                          $err = 'Nama pengeluaran wajib diisi.';
    elseif ($d['amount'] <= 0 || $d['amount'] > 999999999999)       $err = 'Jumlah pengeluaran tidak valid.';
    elseif (!valid_date($d['expense_date']))                        $err = 'Tanggal tidak valid.';
    elseif (!in_array($d['category'], EXPENSE_CATEGORIES, true))    $err = 'Kategori tidak valid.';
    elseif (!in_array($d['split_type'], ['equal', 'custom'], true)) $err = 'Metode split tidak valid.';
    elseif (!in_array($d['paid_by'], $memberIds, true))             $err = 'Pembayar harus anggota trip.';
    elseif (array_diff($splitUsers, $memberIds))                    $err = 'Ada peserta split yang bukan anggota trip.';
    else {
        try {
            $splits = build_splits($d['split_type'], $d['amount'], $splitUsers, $custom);
        } catch (InvalidArgumentException $ex) {
            $err = $ex->getMessage();
        }
    }
    if ($err) { flash('error', $err); redirect($back . ($id ? '&edit=' . $id : '')); }

    $pdo = db();
    if ($id) {
        $st = $pdo->prepare('SELECT 1 FROM expenses WHERE id = ? AND trip_id = ?');
        $st->execute([$id, $tripId]);
        if (!$st->fetch()) { flash('error', 'Pengeluaran tidak ditemukan.'); redirect($back); }
    }

    $pdo->beginTransaction();
    try {
        if ($id) {
            $pdo->prepare('UPDATE expenses SET name=?,amount=?,expense_date=?,category=?,paid_by=?,split_type=?,notes=? WHERE id=? AND trip_id=?')
                ->execute([$d['name'], $d['amount'], $d['expense_date'], $d['category'], $d['paid_by'], $d['split_type'], $d['notes'], $id, $tripId]);
            $pdo->prepare('DELETE FROM expense_splits WHERE expense_id = ?')->execute([$id]);
            $expenseId = $id;
        } else {
            $pdo->prepare('INSERT INTO expenses (trip_id,name,amount,expense_date,category,paid_by,split_type,notes) VALUES (?,?,?,?,?,?,?,?)')
                ->execute([$tripId, $d['name'], $d['amount'], $d['expense_date'], $d['category'], $d['paid_by'], $d['split_type'], $d['notes']]);
            $expenseId = (int)$pdo->lastInsertId();
        }
        $ins = $pdo->prepare('INSERT INTO expense_splits (expense_id,user_id,amount) VALUES (?,?,?)');
        foreach ($splits as $uid => $amt) $ins->execute([$expenseId, $uid, $amt]);
        $pdo->commit();
    } catch (Throwable $ex) {
        $pdo->rollBack();
        flash('error', 'Gagal menyimpan pengeluaran.');
        redirect($back);
    }
    clear_old();
    flash('success', $id ? 'Pengeluaran diperbarui.' : 'Pengeluaran ditambahkan.');
    redirect($back);
}

if ($action === 'delete') {
    // expense_splits ikut terhapus via ON DELETE CASCADE
    db()->prepare('DELETE FROM expenses WHERE id = ? AND trip_id = ?')->execute([(int)($_POST['id'] ?? 0), $tripId]);
    flash('success', 'Pengeluaran dihapus.');
    redirect($back);
}

http_response_code(400);
exit('Aksi tidak dikenal.');
