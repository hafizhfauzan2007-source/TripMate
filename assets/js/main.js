/* TripMate — main.js : helper global + interaksi umum */
(function () {
  'use strict';
  const TM = (window.TM = {});

  /* ---------- Helper ---------- */
  // Format Rupiah: 1350000 -> "Rp1.350.000" (desimal hanya jika ada)
  TM.rupiah = function (n) {
    n = Number(n) || 0;
    const parts = Math.abs(n).toFixed(2).split('.');
    const grouped = parts[0].replace(/\B(?=(\d{3})+(?!\d))/g, '.');
    return (n < 0 ? '-' : '') + 'Rp' + (parts[1] === '00' ? grouped : grouped + ',' + parts[1]);
  };
  TM.signedRupiah = function (n) {
    n = Number(n) || 0;
    return (n > 0 ? '+' : n < 0 ? '-' : '') + TM.rupiah(Math.abs(n));
  };
  // Hitung dalam sen (integer) agar tidak kena masalah floating point
  TM.toCents = function (v) { return Math.round((parseFloat(v) || 0) * 100); };

  TM.showError = function (form, msg) {
    const p = form.querySelector('.field-error');
    if (p) { p.textContent = msg; p.hidden = false; }
  };
  TM.clearError = function (form) {
    const p = form.querySelector('.field-error');
    if (p) { p.textContent = ''; p.hidden = true; }
  };

  TM.toast = function (msg, type) {
    let box = document.getElementById('toastBox');
    if (!box) {
      box = document.createElement('div');
      box.id = 'toastBox';
      box.className = 'toast-box';
      document.body.appendChild(box);
    }
    const t = document.createElement('div');
    t.className = 'toast toast-' + (type || 'error');
    t.textContent = msg;
    box.appendChild(t);
    setTimeout(function () { t.remove(); }, 3500);
  };

  // POST FormData, balasan diharapkan JSON
  TM.post = async function (url, formData) {
    const res = await fetch(url, {
      method: 'POST', body: formData, headers: { 'X-Requested-With': 'XMLHttpRequest' },
    });
    return res.json();
  };

  /* ---------- Interaksi umum ---------- */
  // Tutup menu mobile setelah link diklik
  document.querySelectorAll('.nav-links a').forEach(function (a) {
    a.addEventListener('click', function () {
      const t = document.getElementById('nav-toggle');
      if (t) t.checked = false;
    });
  });

  // Alert sukses hilang otomatis
  document.querySelectorAll('.alert-success').forEach(function (al) {
    setTimeout(function () {
      al.style.transition = 'opacity .4s';
      al.style.opacity = '0';
      setTimeout(function () { al.remove(); }, 450);
    }, 5000);
  });

  // Hilangkan tanda invalid saat pengguna mengetik lagi
  document.addEventListener('input', function (e) {
    if (e.target.classList && e.target.classList.contains('invalid')) e.target.classList.remove('invalid');
  });

  /* ---------- Validasi form Itinerary & Places ---------- */
  document.querySelectorAll('form[data-validate="itinerary"], form[data-validate="place"]').forEach(function (form) {
    form.addEventListener('submit', function (e) {
      const isItin = form.dataset.validate === 'itinerary';
      const nameEl = form.elements[isItin ? 'activity' : 'name'];
      const cost = form.elements['estimated_cost'];
      const date = form.elements['day_date'];
      let msg = '', bad = null;

      if (!nameEl.value.trim()) { msg = isItin ? 'Nama aktivitas wajib diisi.' : 'Nama tempat wajib diisi.'; bad = nameEl; }
      else if (cost && cost.value !== '' && Number(cost.value) < 0) { msg = 'Estimasi biaya tidak boleh negatif.'; bad = cost; }
      else if (isItin && date && date.min && (date.value < date.min || date.value > date.max)) {
        msg = 'Tanggal harus berada dalam rentang perjalanan.'; bad = date;
      }
      if (msg) {
        e.preventDefault();
        bad.classList.add('invalid');
        TM.showError(form, msg);
        bad.focus();
      } else {
        TM.clearError(form);
      }
    });
  });
})();
