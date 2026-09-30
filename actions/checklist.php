<?php
require_once __DIR__ . '/../includes/functions.php';
require_post();

$tripId = (int)($_POST['trip_id'] ?? 0);
$trip   = require_trip_access($tripId);
$action = $_POST['action'] ?? '';
$ajax   = ($_POST['ajax'] ?? '') === '1';   // dikirim oleh JavaScript (fetch)

function checklist_progress(int $tripId): array {
    $st = db()->prepare('SELECT COUNT(*) total, COALESCE(SUM(is_checked),0) done FROM checklists WHERE trip_id = ?');
    $st->execute([$tripId]);
    $r = $st->fetch();
    $total = (int)$r['total']; $done = (int)$r['done'];
    return ['total' => $total, 'done' => $done, 'percent' => $total ? (int)round($done / $total * 100) : 0];
}

// Balas JSON untuk AJAX, atau flash + redirect untuk form biasa
function respond(bool $ok, string $msg, int $tripId, bool $ajax, array $extra = []): void {
    if ($ajax) {
        header('Content-Type: application/json');
        echo json_encode(['ok' => $ok, 'message' => $msg, 'progress' => checklist_progress($tripId)] + $extra);
        exit;
    }
    flash($ok ? 'success' : 'error', $msg);
    redirect('trip/checklist.php?id=' . $tripId);
}

if ($action === 'add') {
    $item = trim($_POST['item_name'] ?? '');
    if ($item === '' || mb_strlen($item) > 150) respond(false, 'Nama item wajib diisi (maks. 150 karakter).', $tripId, $ajax);
    db()->prepare('INSERT INTO checklists (trip_id,item_name) VALUES (?,?)')->execute([$tripId, $item]);
    respond(true, 'Item ditambahkan.', $tripId, $ajax, ['id' => (int)db()->lastInsertId(), 'item_name' => $item]);
}

if ($action === 'toggle') {
    $checked = (($_POST['checked'] ?? '0') === '1') ? 1 : 0;
    db()->prepare('UPDATE checklists SET is_checked = ? WHERE id = ? AND trip_id = ?')
        ->execute([$checked, (int)($_POST['id'] ?? 0), $tripId]);
    respond(true, 'Status diperbarui.', $tripId, $ajax);
}

if ($action === 'delete') {
    db()->prepare('DELETE FROM checklists WHERE id = ? AND trip_id = ?')->execute([(int)($_POST['id'] ?? 0), $tripId]);
    respond(true, 'Item dihapus.', $tripId, $ajax);
}

http_response_code(400);
exit('Aksi tidak dikenal.');
