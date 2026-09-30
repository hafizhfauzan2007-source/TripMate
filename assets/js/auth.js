/* TripMate — auth.js : validasi login & register */
(function () {
  'use strict';
  const emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;

  function fail(form, el, msg, e) {
    e.preventDefault();
    if (el) { el.classList.add('invalid'); el.focus(); }
    TM.showError(form, msg);
  }

  const login = document.getElementById('loginForm');
  if (login) {
    login.addEventListener('submit', function (e) {
      const email = login.elements['email'], pass = login.elements['password'];
      if (!emailRe.test(email.value.trim())) return fail(login, email, 'Format email tidak valid.', e);
      if (!pass.value) return fail(login, pass, 'Password wajib diisi.', e);
      TM.clearError(login);
    });
  }

  const reg = document.getElementById('registerForm');
  if (reg) {
    const pass = reg.elements['password'], confirm = reg.elements['confirm_password'];

    // Cek kecocokan password secara langsung saat mengetik
    function checkMatch() {
      if (confirm.value && confirm.value !== pass.value) TM.showError(reg, 'Konfirmasi password tidak cocok.');
      else TM.clearError(reg);
    }
    confirm.addEventListener('input', checkMatch);
    pass.addEventListener('input', function () { if (confirm.value) checkMatch(); });

    reg.addEventListener('submit', function (e) {
      const name = reg.elements['name'], email = reg.elements['email'];
      if (!name.value.trim()) return fail(reg, name, 'Nama wajib diisi.', e);
      if (!emailRe.test(email.value.trim())) return fail(reg, email, 'Format email tidak valid.', e);
      if (pass.value.length < 6) return fail(reg, pass, 'Password minimal 6 karakter.', e);
      if (pass.value !== confirm.value) return fail(reg, confirm, 'Konfirmasi password tidak cocok.', e);
      TM.clearError(reg);
    });
  }
})();
