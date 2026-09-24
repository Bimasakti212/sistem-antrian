# Sistem Antrean Pelayanan Kecamatan

Aplikasi antrean Laravel 13, PHP 8.3, Blade, Bootstrap 5, MySQL, dan Web Speech API. Nomor dibuat per tanggal dan dialokasikan dalam transaksi dengan baris counter terkunci serta indeks unik tanggal/nomor.

## Persiapan Laragon

1. Buat database MySQL bernama `sistem_antrean` di Laragon.
2. Buka terminal pada folder project, salin `.env.example` menjadi `.env`, lalu atur `APP_URL`, `DB_DATABASE=sistem_antrean`, `DB_USERNAME=root` dan kata sandi database Anda.
3. Atur `STAFF_EMAIL`, `STAFF_NAME`, serta `STAFF_PASSWORD` pada `.env`. Ganti nilai kata sandi contoh sebelum penggunaan.
4. Jalankan:

```powershell
composer install
php artisan key:generate
php artisan migrate --seed
php artisan serve
```

Buka `http://127.0.0.1:8000/antrian` untuk warga, `/login` untuk petugas, dan `/qr` setelah masuk untuk menampilkan QR siap cetak. Alternatifnya, letakkan folder ini di `C:\laragon\www`, lalu gunakan virtual host Laragon.

Untuk menjalankan pengujian otomatis, aktifkan ekstensi `pdo_sqlite` dan `sqlite3` pada PHP Laragon, lalu jalankan `php artisan test`.

## Alur

- Pendaftaran warga dan petugas menyimpan antrean pada tabel yang sama.
- Pendaftaran mengunci counter tanggal hari ini dalam transaksi; indeks unik database menjadi perlindungan tambahan dari nomor ganda.
- Petugas masuk dengan akun hasil seeder. Panggilan antrean dikirim melalui fetch; browser mengucapkan nomor melalui Web Speech API. Izinkan suara pada browser dan pilih perangkat speaker kantor.
- Tombol panggil ulang mengulang ucapan. Lewati dan selesai mencatat perubahan status dan waktu pada antrean; tindakan dicatat di `queue_logs`.
- Riwayat dapat difilter berdasarkan tanggal.

## File inti

- `routes/web.php`: halaman warga, login, dashboard, dan aksi antrean.
- `app/Http/Controllers/QueueController.php`: validasi, pembuatan nomor atomik, transisi status, riwayat.
- `database/migrations`: counter harian, antrean, dan log.
- `resources/views`: halaman Blade berbahasa Indonesia.
- `public/js/app.js` dan `public/css/app.css`: navigasi geser, suara, fetch, dan antarmuka.

## Catatan operasional

QR pada halaman `/qr` memuat URL aplikasi saat ini. Cetak QR setelah `APP_URL` diarahkan ke alamat yang dapat dijangkau ponsel warga melalui jaringan kantor. QR dibuat oleh layanan eksternal agar tidak menambah dependensi PHP; halaman tersebut mengirim URL antrean ke layanan QR saat dimuat. Perangkat komputer petugas harus mengizinkan suara browser.
