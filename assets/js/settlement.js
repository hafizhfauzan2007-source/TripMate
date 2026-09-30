/* TripMate — settlement.js : hitung ulang saldo & settlement di sisi browser */
(function () {
  'use strict';
  const app = document.getElementById('settlementApp');
  if (!app) return;

  const cards = Array.from(app.querySelectorAll('.balance-card'));
  const listEl = document.getElementById('settlementList');
  const copyBtn = document.getElementById('copySummary');
  const totalEl = document.getElementById('settleTotal');

  // Data dasar dari atribut data-* (hasil query PHP), dihitung dalam sen
  const people = cards.map(function (c) {
    const paid = TM.toCents(c.dataset.paid), share = TM.toCents(c.dataset.share);
    return { card: c, name: c.querySelector('.member-line strong').textContent.trim(), paid: paid, share: share, balance: paid - share };
  });

  // 1) Perbarui tampilan saldo tiap anggota
  people.forEach(function (p) {
    const cls = p.balance > 0 ? 'pos' : p.balance < 0 ? 'neg' : 'zero';
    p.card.classList.remove('pos', 'neg', 'zero');
    p.card.classList.add(cls);
    p.card.querySelector('.balance-line strong').textContent = TM.signedRupiah(p.balance / 100);
  });

  const totalPaid = people.reduce(function (a, p) { return a + p.paid; }, 0);
  if (totalEl) totalEl.textContent = TM.rupiah(totalPaid / 100);

  // 2) Hitung settlement: pasangkan yang berhutang dengan yang piutang terbesar
  const creditors = people.filter(function (p) { return p.balance > 0; })
    .map(function (p) { return { name: p.name, amt: p.balance }; }).sort(function (a, b) { return b.amt - a.amt; });
  const debtors = people.filter(function (p) { return p.balance < 0; })
    .map(function (p) { return { name: p.name, amt: -p.balance }; }).sort(function (a, b) { return b.amt - a.amt; });

  const transfers = [];
  let i = 0, j = 0;
  while (i < debtors.length && j < creditors.length) {
    const pay = Math.min(debtors[i].amt, creditors[j].amt);
    transfers.push({ from: debtors[i].name, to: creditors[j].name, amount: pay });
    debtors[i].amt -= pay;
    creditors[j].amt -= pay;
    if (debtors[i].amt === 0) i++;
    if (creditors[j].amt === 0) j++;
  }

  // 3) Tampilkan
  listEl.innerHTML = '';
  if (totalPaid <= 0) {
    listEl.innerHTML = '<p class="muted">Belum ada pengeluaran yang dicatat.</p>';
  } else if (!transfers.length) {
    listEl.innerHTML = '<p>🎉 Semua sudah impas. Tidak ada yang perlu membayar.</p>';
  } else {
    transfers.forEach(function (t) {
      const row = document.createElement('div');
      row.className = 'settle-item';
      const left = document.createElement('span');
      const from = document.createElement('strong'); from.textContent = t.from;
      const arrow = document.createElement('span'); arrow.className = 'arrow'; arrow.textContent = '→';
      const to = document.createElement('strong'); to.textContent = t.to;
      left.append(from, ' ', arrow, ' ', to);
      const amt = document.createElement('strong'); amt.className = 'settle-amount'; amt.textContent = TM.rupiah(t.amount / 100);
      row.append(left, amt);
      listEl.appendChild(row);
    });
  }

  // 4) Tombol salin ringkasan (untuk dibagikan ke grup chat)
  if (copyBtn && transfers.length) {
    copyBtn.hidden = false;
    copyBtn.addEventListener('click', function () {
      const h1 = document.querySelector('.trip-head h1');
      const lines = ['TripMate — ' + (h1 ? h1.textContent.trim() : 'Trip'),
                     'Total pengeluaran: ' + TM.rupiah(totalPaid / 100), '', 'Settlement:'];
      transfers.forEach(function (t) { lines.push(t.from + ' → ' + t.to + ': ' + TM.rupiah(t.amount / 100)); });
      const text = lines.join('\n');

      const done = function () {
        const old = copyBtn.textContent;
        copyBtn.textContent = 'Copied!';
        setTimeout(function () { copyBtn.textContent = old; }, 1500);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done, function () { TM.toast('Gagal menyalin.'); });
      } else {
        const ta = document.createElement('textarea');
        ta.value = text; document.body.appendChild(ta); ta.select();
        try { document.execCommand('copy'); done(); } catch (err) { TM.toast('Gagal menyalin.'); }
        ta.remove();
      }
    });
  }
})();
