/* TripMate — checklist.js : tambah / centang / hapus tanpa reload + progress otomatis */
(function () {
  'use strict';
  const app = document.getElementById('checklistApp');
  if (!app) return;

  const list = document.getElementById('checkList');
  const addForm = document.getElementById('addItemForm');
  const quickForm = document.getElementById('quickAdd');
  const nameInput = document.getElementById('itemName');
  const bar = document.getElementById('progressBar');
  const text = document.getElementById('progressText');
  const count = document.getElementById('progressCount');
  const empty = document.getElementById('emptyNote');
  const tripId = app.dataset.tripId;

  function updateProgress() {
    const items = Array.from(list.querySelectorAll('.check-item'));
    const done = items.filter(function (li) { return li.classList.contains('done'); }).length;
    const total = items.length;
    const pct = total ? Math.round((done / total) * 100) : 0;
    bar.style.width = pct + '%';
    text.textContent = 'Progress: ' + pct + '%';
    count.textContent = done + ' of ' + total + ' items';
    empty.hidden = total > 0;
  }

  function setDone(li, done) {
    li.classList.toggle('done', done);
    li.querySelector('.check-btn').textContent = done ? '✓' : '';
    li.querySelector('input[name="checked"]').value = done ? '0' : '1'; // nilai berikutnya jika diklik lagi
  }

  function createItem(id, name, actionUrl) {
    const li = document.createElement('li');
    li.className = 'check-item';
    li.dataset.id = id;
    const csrf = window.TRIPMATE.csrf;
    li.innerHTML =
      '<form method="post" class="toggle-form">' +
        '<input type="hidden" name="csrf" value="' + csrf + '">' +
        '<input type="hidden" name="action" value="toggle">' +
        '<input type="hidden" name="trip_id" value="' + tripId + '">' +
        '<input type="hidden" name="id" value="' + id + '">' +
        '<input type="hidden" name="checked" value="1">' +
        '<button type="submit" class="check-btn" aria-label="Tandai selesai"></button>' +
      '</form>' +
      '<span class="check-label"></span>' +
      '<form method="post" class="delete-form">' +
        '<input type="hidden" name="csrf" value="' + csrf + '">' +
        '<input type="hidden" name="action" value="delete">' +
        '<input type="hidden" name="trip_id" value="' + tripId + '">' +
        '<input type="hidden" name="id" value="' + id + '">' +
        '<button type="submit" class="btn btn-ghost btn-sm text-danger" aria-label="Hapus">✕</button>' +
      '</form>';
    li.querySelectorAll('form').forEach(function (f) { f.action = actionUrl; });
    li.querySelector('.check-label').textContent = name; // textContent → aman dari XSS
    return li;
  }

  async function addItem(name, sourceForm) {
    name = name.trim();
    if (!name) { TM.toast('Nama item wajib diisi.'); return false; }
    const fd = new FormData(sourceForm);
    fd.set('item_name', name);
    fd.set('ajax', '1');
    try {
      const data = await TM.post(addForm.action, fd);
      if (!data.ok) { TM.toast(data.message); return false; }
      const li = createItem(data.id, name, addForm.action);
      const firstDone = list.querySelector('.check-item.done');   // item baru di antara yang belum & sudah selesai
      list.insertBefore(li, firstDone);
      updateProgress();
      return true;
    } catch (err) {
      TM.toast('Gagal terhubung ke server.');
      return false;
    }
  }

  // Tambah lewat input
  addForm.addEventListener('submit', async function (e) {
    e.preventDefault();
    if (await addItem(nameInput.value, addForm)) { nameInput.value = ''; nameInput.focus(); }
  });

  // Quick add (tombol saran)
  if (quickForm) {
    quickForm.addEventListener('submit', async function (e) {
      e.preventDefault();
      const btn = e.submitter;
      if (!btn) return;
      if (await addItem(btn.value, quickForm)) {
        btn.remove();
        if (!quickForm.querySelector('button')) quickForm.hidden = true;
      }
    });
  }

  // Centang / hapus (event delegation, juga berlaku untuk item yang baru ditambah)
  list.addEventListener('submit', async function (e) {
    const form = e.target;
    const li = form.closest('.check-item');

    if (form.classList.contains('toggle-form')) {
      e.preventDefault();
      const fd = new FormData(form);
      fd.set('ajax', '1');
      const willCheck = form.elements['checked'].value === '1';
      setDone(li, willCheck);               // update tampilan dulu (optimistic)
      updateProgress();
      try {
        const data = await TM.post(form.action, fd);
        if (!data.ok) throw new Error(data.message);
      } catch (err) {
        setDone(li, !willCheck);            // batalkan jika gagal
        updateProgress();
        TM.toast('Gagal memperbarui item.');
      }
    }

    if (form.classList.contains('delete-form')) {
      e.preventDefault();
      const fd = new FormData(form);
      fd.set('ajax', '1');
      li.style.opacity = '.5';
      try {
        const data = await TM.post(form.action, fd);
        if (!data.ok) throw new Error(data.message);
        li.remove();
        updateProgress();
      } catch (err) {
        li.style.opacity = '';
        TM.toast('Gagal menghapus item.');
      }
    }
  });

  updateProgress();
})();
