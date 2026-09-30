# TripMate — Travel Planner & Split Bill

Website untuk merencanakan perjalanan sekaligus mengelola pengeluaran dan split bill bersama teman.
Stack: HTML, CSS, JavaScript, PHP (native, PDO), MySQL.

## Cara menjalankan (XAMPP / Laragon)

1. Salin folder `tripmate/` ke `C:\xampp\htdocs\` (XAMPP) atau `C:\laragon\www\` (Laragon).
2. Jalankan **Apache** dan **MySQL**.
3. Buka `http://localhost/phpmyadmin` → tab **Import** → pilih `database/tripmate.sql` → **Go**.
4. Cek `config/database.php` (`DB_USER`, `DB_PASS`, `BASE_URL`).
5. Buka `http://localhost/tripmate/`.

### Alternatif: PHP built-in server
```bash
cd tripmate
php -S localhost:8000
```
Lalu ubah `BASE_URL` di `config/database.php` menjadi `''` (string kosong) dan buka `http://localhost:8000/`.

## Struktur singkat
```
config/     koneksi database + session
includes/   helper, header/footer, logika split & settlement
actions/    handler POST (login, trip, expense, dll.)
trip/       halaman per perjalanan (overview, itinerary, places, expenses, ...)
assets/     css + js
database/   tripmate.sql
```
Alur: halaman (tampilan) → `actions/*.php` (proses & validasi) → MySQL → redirect kembali ke halaman.

## Troubleshooting
| Masalah | Solusi |
|---|---|
| `Unknown database 'tripmate'` | `database/tripmate.sql` belum di-import |
| `Access denied for user` | Sesuaikan `DB_USER` / `DB_PASS` |
| `could not find driver` | Aktifkan `extension=pdo_mysql` di `php.ini`, restart Apache |
| Halaman 404 / CSS tidak muncul | `BASE_URL` tidak sama dengan nama folder |
| `headers already sent` | Ada spasi/BOM sebelum `<?php`; simpan file sebagai UTF-8 tanpa BOM |
| `Invalid CSRF token` | Sesi kedaluwarsa; refresh halaman lalu ulangi |
| Halaman putih | Aktifkan `display_errors=On` di `php.ini` atau cek `apache/logs/error.log` |
