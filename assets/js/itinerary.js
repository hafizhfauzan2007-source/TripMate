/* TripMate — itinerary.js : filter itinerary per tanggal (tanpa reload) */
(function () {
  'use strict';
  const bar = document.getElementById('dayFilter');
  const list = document.getElementById('itineraryList');
  if (!bar || !list) return;

  const chips = Array.from(bar.querySelectorAll('.chip'));
  const groups = Array.from(list.querySelectorAll('.day-group'));
  const dateInput = document.getElementById('day_date');
  const idInput = document.querySelector('#itineraryForm input[name="id"]');

  function apply(filter) {
    chips.forEach(function (c) { c.classList.toggle('active', c.dataset.filter === filter); });

    let visible = 0;
    groups.forEach(function (g) {
      const show = filter === 'all' || g.dataset.date === filter;
      g.hidden = !show;
      if (show) visible++;
    });

    // Pesan jika hari yang dipilih belum punya aktivitas
    let note = document.getElementById('noDayItems');
    if (groups.length && visible === 0) {
      if (!note) {
        note = document.createElement('div');
        note.id = 'noDayItems';
        note.className = 'card empty-state';
        note.innerHTML = '<div class="empty-icon">🗓️</div><h3>Belum ada aktivitas di hari ini</h3><p class="muted">Tambahkan lewat form.</p>';
        list.appendChild(note);
      }
    } else if (note) {
      note.remove();
    }

    // Form "Add Activity" ikut memakai tanggal yang dipilih (bukan saat edit)
    if (dateInput && idInput && idInput.value === '0' && filter !== 'all') dateInput.value = filter;

    // Simpan pilihan di URL agar bisa di-refresh / dibagikan
    const url = new URL(window.location.href);
    if (filter === 'all') url.searchParams.delete('date'); else url.searchParams.set('date', filter);
    history.replaceState(null, '', url);
  }

  chips.forEach(function (chip) {
    chip.addEventListener('click', function (e) {
      e.preventDefault();
      apply(chip.dataset.filter);
    });
  });

  const active = bar.querySelector('.chip.active');
  apply(active ? active.dataset.filter : 'all');
})();
