<?php
require_once __DIR__ . '/functions.php';

/**
 * Bangun pembagian per orang.
 * $type   : 'equal' | 'custom'
 * $custom : [user_id => nominal] (dipakai jika custom)
 * Return  : [user_id => nominal] atau lempar InvalidArgumentException
 */
function build_splits(string $type, float $amount, array $userIds, array $custom = []): array {
    $userIds = array_values(array_unique(array_map('intval', $userIds)));
    if (!$userIds) throw new InvalidArgumentException('Pilih minimal satu anggota yang ikut menanggung.');

    $totalCents = (int)round($amount * 100);
    $splits = [];

    if ($type === 'equal') {
        $n = count($userIds);
        $base = intdiv($totalCents, $n);
        $rest = $totalCents - $base * $n;      // sisa dibagikan 1 sen ke orang-orang pertama
        foreach ($userIds as $i => $uid) {
            $splits[$uid] = ($base + ($i < $rest ? 1 : 0)) / 100;
        }
    } else {
        $sum = 0;
        foreach ($userIds as $uid) {
            $c = (int)round(((float)($custom[$uid] ?? 0)) * 100);
            if ($c < 0) throw new InvalidArgumentException('Nominal split tidak boleh negatif.');
            $splits[$uid] = $c / 100;
            $sum += $c;
        }
        if ($sum !== $totalCents) {
            throw new InvalidArgumentException('Total custom split harus sama dengan total pengeluaran.');
        }
    }
    return $splits;
}

/** Saldo tiap anggota: paid, share, balance (+ = kelebihan bayar, - = masih hutang) */
function calculate_balances(int $tripId): array {
    $pdo = db();
    $bal = [];
    foreach (get_trip_members($tripId) as $m) {
        $bal[$m['user_id']] = ['user_id' => $m['user_id'], 'name' => $m['name'],
                               'paid' => 0.0, 'share' => 0.0, 'balance' => 0.0];
    }

    $st = $pdo->prepare('SELECT paid_by, SUM(amount) total FROM expenses WHERE trip_id = ? GROUP BY paid_by');
    $st->execute([$tripId]);
    foreach ($st as $r) if (isset($bal[$r['paid_by']])) $bal[$r['paid_by']]['paid'] = (float)$r['total'];

    $st = $pdo->prepare(
        'SELECT s.user_id, SUM(s.amount) total FROM expense_splits s
         JOIN expenses e ON e.id = s.expense_id WHERE e.trip_id = ? GROUP BY s.user_id'
    );
    $st->execute([$tripId]);
    foreach ($st as $r) if (isset($bal[$r['user_id']])) $bal[$r['user_id']]['share'] = (float)$r['total'];

    foreach ($bal as &$b) $b['balance'] = round($b['paid'] - $b['share'], 2);
    return array_values($bal);
}

/** Ringkasan "siapa bayar ke siapa" (greedy, meminimalkan jumlah transfer) */
function calculate_settlements(array $balances): array {
    $cred = []; $debt = [];
    foreach ($balances as $b) {
        $c = (int)round($b['balance'] * 100);
        if ($c > 0)      $cred[] = ['name' => $b['name'], 'amt' => $c];
        elseif ($c < 0)  $debt[] = ['name' => $b['name'], 'amt' => -$c];
    }
    usort($cred, fn($a, $b) => $b['amt'] <=> $a['amt']);
    usort($debt, fn($a, $b) => $b['amt'] <=> $a['amt']);

    $out = []; $i = $j = 0;
    while ($i < count($debt) && $j < count($cred)) {
        $pay = min($debt[$i]['amt'], $cred[$j]['amt']);
        $out[] = ['from' => $debt[$i]['name'], 'to' => $cred[$j]['name'], 'amount' => $pay / 100];
        $debt[$i]['amt'] -= $pay;
        $cred[$j]['amt'] -= $pay;
        if ($debt[$i]['amt'] === 0) $i++;
        if ($cred[$j]['amt'] === 0) $j++;
    }
    return $out;
}
