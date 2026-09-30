/* TripMate — trip-form.js : validasi create/edit trip */
(function () {
  'use strict';
  const form = document.getElementById('tripForm');
  if (!form) return;
  const start = form.elements['start_date'], end = form.elements['end_date'], budget = form.elements['budget'];
  const errBox = document.getElementById('tripError');

  // Tanggal selesai tidak boleh sebelum tanggal mulai
  function syncDates() {
    if (!start.value) return;
    end.min = start.value;
    if (end.value && end.value < start.value) end.value = start.value;
  }
  start.addEventListener('change', syncDates);
  syncDates();

  form.addEventListener('submit', function (e) {
    let msg = '', bad = null;
    if (!form.elements['name'].value.trim()) { msg = 'Nama perjalanan wajib diisi.'; bad = form.elements['name']; }
    else if (!form.elements['destination'].value.trim()) { msg = 'Destinasi wajib diisi.'; bad = form.elements['destination']; }
    else if (!start.value || !end.value) { msg = 'Tanggal mulai dan selesai wajib diisi.'; bad = start.value ? end : start; }
    else if (end.value < start.value) { msg = 'Tanggal selesai tidak boleh sebelum tanggal mulai.'; bad = end; }
    else if (budget.value !== '' && Number(budget.value) < 0) { msg = 'Budget tidak boleh negatif.'; bad = budget; }

    if (msg) {
      e.preventDefault();
      bad.classList.add('invalid');
      errBox.textContent = msg;
      errBox.hidden = false;
      bad.focus();
    } else {
      errBox.hidden = true;
    }
  });
})();
