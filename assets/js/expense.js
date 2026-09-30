/* TripMate — expense.js : Equal / Custom split + validasi total */
(function () {
  'use strict';
  const form = document.getElementById('expenseForm');
  if (!form) return;

  const $ = function (id) { return document.getElementById(id); };
  const amountEl = $('expenseAmount');
  const radios = Array.from(form.querySelectorAll('input[name="split_type"]'));
  const rows = Array.from(form.querySelectorAll('.split-row')).map(function (row) {
    return { row: row, check: row.querySelector('.split-check'), input: row.querySelector('.split-custom') };
  });
  const sumTotal = $('sumTotal'), sumMembers = $('sumMembers'), sumEach = $('sumEach');
  const sumCustom = $('sumCustom'), sumDiff = $('sumDiff'), errBox = $('splitError');
  const selectAll = $('selectAll'), fillBtn = $('fillEqual');

  let manual = rows.some(function (r) { return r.input.value !== ''; }); // sudah diisi manual / mode edit
  let attempted = false;                                                  // sudah pernah mencoba submit

  const cents = TM.toCents;
  const splitType = function () { const r = radios.find(function (x) { return x.checked; }); return r ? r.value : 'equal'; };
  const selected = function () { return rows.filter(function (r) { return r.check.checked; }); };

  // Isi nominal custom secara rata (sisa sen dibagikan ke anggota pertama, sama seperti server)
  function fillEqual() {
    const sel = selected();
    const total = cents(amountEl.value);
    let rest = 0, base = 0;
    if (sel.length) { base = Math.floor(total / sel.length); rest = total - base * sel.length; }
    rows.forEach(function (r) {
      if (!r.check.checked) { r.input.value = ''; return; }
      const c = base + (rest > 0 ? 1 : 0);
      if (rest > 0) rest--;
      r.input.value = total > 0 ? (c / 100).toFixed(2).replace(/\.00$/, '') : '';
    });
  }

  function analyze() {
    const type = splitType(), total = cents(amountEl.value), sel = selected();
    const res = { type: type, total: total, count: sel.length, sum: 0, diff: 0, error: '' };
    if (type === 'custom') {
      res.sum = sel.reduce(function (a, r) { return a + cents(r.input.value); }, 0);
      res.diff = total - res.sum;
      if (sel.some(function (r) { return cents(r.input.value) < 0; })) {
        res.error = 'Nominal split tidak boleh negatif.';
      } else if (total > 0 && res.diff !== 0) {
        res.error = 'Total custom split (' + TM.rupiah(res.sum / 100) + ') harus sama dengan total pengeluaran (' +
                    TM.rupiah(total / 100) + '). Selisih: ' + TM.rupiah(Math.abs(res.diff) / 100) + '.';
      }
    }
    return res;
  }

  function showError(msg) {
    errBox.textContent = msg;
    errBox.hidden = !msg;
  }

  function update() {
    const type = splitType();
    form.classList.toggle('is-custom', type === 'custom');

    rows.forEach(function (r) {
      r.row.classList.toggle('is-off', !r.check.checked);
      // Input custom hanya aktif (dan ikut terkirim) jika mode custom & anggota dipilih
      r.input.disabled = !(type === 'custom' && r.check.checked);
    });

    if (type === 'custom' && !manual) fillEqual();

    const a = analyze();
    sumTotal.textContent = TM.rupiah(a.total / 100);
    sumMembers.textContent = a.count;

    if (a.count > 0) {
      const base = Math.floor(a.total / a.count), rest = a.total - base * a.count;
      sumEach.textContent = (rest ? '≈ ' : '') + TM.rupiah(base / 100);
    } else {
      sumEach.textContent = TM.rupiah(0);
    }

    sumCustom.textContent = TM.rupiah(a.sum / 100);
    sumDiff.textContent = TM.rupiah(a.diff / 100);
    sumDiff.classList.toggle('text-success', a.total > 0 && a.diff === 0);
    sumDiff.classList.toggle('text-danger', a.diff !== 0);

    if (selectAll) selectAll.textContent = rows.every(function (r) { return r.check.checked; }) ? 'Deselect all' : 'Select all';

    // Pesan error langsung hanya jika user sudah mengisi nominal / sudah mencoba submit
    if (a.error && (a.sum > 0 || attempted)) showError(a.error);
    else if (attempted && a.count === 0) showError('Pilih minimal satu anggota yang ikut menanggung.');
    else showError('');
  }

  /* ---------- Event ---------- */
  amountEl.addEventListener('input', update);
  radios.forEach(function (r) { r.addEventListener('change', update); });
  rows.forEach(function (r) {
    r.check.addEventListener('change', update);
    r.input.addEventListener('input', function () { manual = true; update(); });
  });

  if (selectAll) {
    selectAll.hidden = false;
    selectAll.addEventListener('click', function () {
      const all = rows.every(function (r) { return r.check.checked; });
      rows.forEach(function (r) { r.check.checked = !all; });
      update();
    });
  }
  if (fillBtn) {
    fillBtn.hidden = false;
    fillBtn.addEventListener('click', function () { manual = false; update(); });
  }

  form.addEventListener('submit', function (e) {
    attempted = true;
    const a = analyze();
    let msg = '';
    if (!form.elements['name'].value.trim()) msg = 'Nama pengeluaran wajib diisi.';
    else if (a.total <= 0) msg = 'Jumlah pengeluaran harus lebih dari 0.';
    else if (a.count === 0) msg = 'Pilih minimal satu anggota yang ikut menanggung.';
    else if (a.error) msg = a.error;

    if (msg) {
      e.preventDefault();
      showError(msg);
      errBox.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
  });

  update();
})();
