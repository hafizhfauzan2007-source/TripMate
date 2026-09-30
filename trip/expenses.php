<?php
require_once __DIR__ . '/../includes/functions.php';
$trip = require_trip_access((int)($_GET['id'] ?? 0));
$tripId = (int)$trip['id'];
$pdo = db();
$members = get_trip_members($tripId);
$allIds = array_map(fn($m) => (int)$m['user_id'], $members);

// Mode edit
$editId = (int)($_GET['edit'] ?? 0);
$editing = null; $editSplits = [];
if ($editId) {
    $st = $pdo->prepare('SELECT * FROM expenses WHERE id = ? AND trip_id = ?');
    $st->execute([$editId, $tripId]);
    $editing = $st->fetch() ?: null;
    if ($editing) {
        $st = $pdo->prepare('SELECT user_id, amount FROM expense_splits WHERE expense_id = ?');
        $st->execute([$editId]);
        $editSplits = $st->fetchAll(PDO::FETCH_KEY_PAIR);
    } else { $editId = 0; }
}

// Nilai awal form: old input > data edit > default
$today = date('Y-m-d');
$f = [
    'name' => '', 'amount' => '', 'category' => 'Food', 'paid_by' => user_id(), 'split_type' => 'equal', 'notes' => '',
    'expense_date' => ($today >= $trip['start_date'] && $today <= $trip['end_date']) ? $today : $trip['start_date'],
    'split_users' => $allIds, 'custom' => [],
];
$old = take_old(['split_type']);
if ($old) {
    $f = array_merge($f, $old);
    $f['split_users'] = array_map('intval', (array)($old['split_users'] ?? []));
    $f['custom'] = (array)($old['custom'] ?? []);
} elseif ($editing) {
    $f = array_merge($f, $editing);
    $f['split_users'] = array_map('intval', array_keys($editSplits));
    $f['custom'] = $editing['split_type'] === 'custom' ? $editSplits : [];
}

// Daftar pengeluaran + rincian split
$st = $pdo->prepare('SELECT e.*, u.name AS paid_name FROM expenses e JOIN users u ON u.id = e.paid_by
                     WHERE e.trip_id = ? ORDER BY e.expense_date DESC, e.id DESC');
$st->execute([$tripId]);
$expenses = $st->fetchAll();

$st = $pdo->prepare('SELECT s.expense_id, u.name, s.amount FROM expense_splits s
                     JOIN users u ON u.id = s.user_id JOIN expenses e ON e.id = s.expense_id
                     WHERE e.trip_id = ? ORDER BY u.name');
$st->execute([$tripId]);
$splitsBy = [];
foreach ($st as $r) $splitsBy[$r['expense_id']][] = $r;
$total = array_sum(array_column($expenses, 'amount'));

$pageTitle = 'Expenses · ' . $trip['name'];
$activeTab = 'expenses';
$pageScripts = ['expense.js'];
$jsData = ['tripId' => $tripId, 'members' => array_map(fn($m) => ['id' => (int)$m['user_id'], 'name' => $m['name']], $members)];
require_once __DIR__ . '/../includes/header.php';
require __DIR__ . '/../includes/trip_nav.php';
$self = BASE_URL . '/trip/expenses.php?id=' . $tripId;
?>
<div class="card form-card" id="expenseFormCard">
  <h3><?= $editId ? 'Edit Expense' : 'Add Expense' ?></h3>
  <form method="post" action="<?= BASE_URL ?>/actions/expense.php" class="form expense-form" id="expenseForm" data-validate="expense">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="save">
    <input type="hidden" name="trip_id" value="<?= $tripId ?>">
    <input type="hidden" name="id" value="<?= $editId ?>">

    <div class="form-grid">
      <div class="field"><label for="name">Expense Name</label>
        <input type="text" id="name" name="name" value="<?= e($f['name']) ?>" placeholder="mis. Hotel" maxlength="150" required></div>
      <div class="field"><label for="expenseAmount">Amount (Rp)</label>
        <input type="number" id="expenseAmount" name="amount" value="<?= e(num_input($f['amount'])) ?>" min="1" step="any" placeholder="600000" required></div>
      <div class="field"><label for="expense_date">Date</label>
        <input type="date" id="expense_date" name="expense_date" value="<?= e($f['expense_date']) ?>" required></div>
      <div class="field"><label for="category">Category</label>
        <select id="category" name="category">
          <?php foreach (EXPENSE_CATEGORIES as $c): ?><option value="<?= $c ?>" <?= $f['category'] === $c ? 'selected' : '' ?>><?= category_icon($c) . ' ' . $c ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label for="paid_by">Paid By</label>
        <select id="paid_by" name="paid_by">
          <?php foreach ($members as $m): ?><option value="<?= (int)$m['user_id'] ?>" <?= (int)$f['paid_by'] === (int)$m['user_id'] ? 'selected' : '' ?>><?= e($m['name']) ?></option><?php endforeach; ?>
        </select></div>
      <div class="field"><label>Split Method</label>
        <div class="segmented">
          <label><input type="radio" name="split_type" value="equal" <?= $f['split_type'] !== 'custom' ? 'checked' : '' ?>><span>Equal Split</span></label>
          <label><input type="radio" name="split_type" value="custom" <?= $f['split_type'] === 'custom' ? 'checked' : '' ?>><span>Custom Split</span></label>
        </div></div>
    </div>

    <div class="field">
      <div class="row-between">
        <label>Split Between</label>
        <span class="row-actions">
          <button type="button" class="btn btn-ghost btn-sm" id="selectAll" hidden>Select all</button>
          <button type="button" class="btn btn-ghost btn-sm split-custom-only" id="fillEqual" hidden>Fill equally</button>
        </span>
      </div>
      <div class="split-list" id="splitMembers">
        <?php foreach ($members as $m): $uid = (int)$m['user_id']; ?>
          <div class="split-row" data-user-id="<?= $uid ?>">
            <label class="check">
              <input type="checkbox" class="split-check" name="split_users[]" value="<?= $uid ?>" <?= in_array($uid, $f['split_users'], true) ? 'checked' : '' ?>>
              <span class="avatar sm"><?= e(initials($m['name'])) ?></span>
              <span><?= e($m['name']) ?></span>
            </label>
            <input type="number" class="split-custom split-custom-only" name="custom[<?= $uid ?>]" value="<?= e(num_input($f['custom'][$uid] ?? '')) ?>" min="0" step="any" placeholder="0" aria-label="Nominal <?= e($m['name']) ?>">
          </div>
        <?php endforeach; ?>
      </div>
    </div>

    <div class="split-summary" id="splitSummary" aria-live="polite">
      <div class="row-between"><span>Total</span><strong id="sumTotal">Rp0</strong></div>
      <div class="row-between"><span>Members</span><strong id="sumMembers"><?= count($f['split_users']) ?></strong></div>
      <div class="row-between split-equal-only"><span>Each person</span><strong id="sumEach">Rp0</strong></div>
      <div class="row-between split-custom-only"><span>Custom total</span><strong id="sumCustom">Rp0</strong></div>
      <div class="row-between split-custom-only"><span>Remaining</span><strong id="sumDiff">Rp0</strong></div>
      <p class="field-error" id="splitError" hidden></p>
    </div>

    <div class="field"><label for="notes">Notes</label>
      <textarea id="notes" name="notes" rows="2"><?= e($f['notes']) ?></textarea></div>
    <div class="row-actions">
      <button type="submit" class="btn btn-primary"><?= $editId ? 'Save Changes' : 'Add Expense' ?></button>
      <?php if ($editId): ?><a href="<?= $self ?>" class="btn btn-ghost">Cancel</a><?php endif; ?>
    </div>
  </form>
</div>

<div class="row-between page-head"><h2>All Expenses</h2><strong>Total: <?= e(rupiah($total)) ?></strong></div>
<?php if (!$expenses): ?>
  <div class="card empty-state"><div class="empty-icon">💸</div><h3>Belum ada pengeluaran</h3><p class="muted">Catat pengeluaran pertamamu lewat form di atas.</p></div>
<?php else: ?>
<div class="card table-card">
  <div class="table-wrap">
    <table class="table responsive-table" id="expenseTable">
      <thead><tr><th>Date</th><th>Expense</th><th>Paid by</th><th>Split</th><th class="num">Amount</th><th></th></tr></thead>
      <tbody>
      <?php foreach ($expenses as $ex): ?>
        <tr>
          <td data-label="Date"><?= e(format_date($ex['expense_date'], 'j M Y')) ?></td>
          <td data-label="Expense"><strong><?= e($ex['name']) ?></strong><br><?= category_badge($ex['category']) ?>
            <?php if ($ex['notes']): ?><div class="small muted"><?= e($ex['notes']) ?></div><?php endif; ?></td>
          <td data-label="Paid by"><?= e($ex['paid_name']) ?></td>
          <td data-label="Split">
            <span class="badge"><?= $ex['split_type'] === 'equal' ? 'Equal' : 'Custom' ?></span>
            <div class="small muted split-detail">
              <?php foreach ($splitsBy[$ex['id']] ?? [] as $s): ?><div><?= e($s['name']) ?> · <?= e(rupiah($s['amount'])) ?></div><?php endforeach; ?>
            </div></td>
          <td data-label="Amount" class="num"><strong><?= e(rupiah($ex['amount'])) ?></strong></td>
          <td class="actions">
            <a href="<?= $self ?>&edit=<?= (int)$ex['id'] ?>#expenseFormCard" class="btn btn-ghost btn-sm">Edit</a>
            <form method="post" action="<?= BASE_URL ?>/actions/expense.php" class="inline-form" onsubmit="return confirm('Hapus pengeluaran ini?');">
              <?= csrf_field() ?>
              <input type="hidden" name="action" value="delete">
              <input type="hidden" name="trip_id" value="<?= $tripId ?>">
              <input type="hidden" name="id" value="<?= (int)$ex['id'] ?>">
              <button type="submit" class="btn btn-ghost btn-sm text-danger">Delete</button>
            </form>
          </td>
        </tr>
      <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>
<?php endif; ?>
<?php require_once __DIR__ . '/../includes/footer.php'; ?>
