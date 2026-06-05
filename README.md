# School Photo Orders

Aplikasi PHP native MVC untuk membuat pesanan foto sekolah, mengunggah logo sekolah, mengunggah foto siswa satuan atau massal, lalu menelusuri pesanan dari panel admin.

Project ini diasumsikan langsung berjalan di server cPanel. Simpan konfigurasi sensitif di `.env`, bukan di file PHP.

## Stack

- PHP 8.2+ dengan ekstensi `pdo_mysql`, `fileinfo`, dan `gd` dengan dukungan JPEG, PNG, dan WEBP.
- MySQL atau MariaDB.
- Apache dengan `mod_rewrite`; `mod_headers` direkomendasikan.
- Frontend memakai Bootstrap CSS CDN, CSS lokal, dan JavaScript lokal tanpa Bootstrap JS atau Bootstrap Icons.

## Struktur

```text
app/                  Controller, model, helper, middleware, dan view
config/               Konfigurasi aplikasi dan database
database/             SQL migrasi/import
docker/               Konfigurasi PHP lokal
public/               Document root, asset publik, upload runtime
public/uploads/       File logo dan foto hasil upload
routes/               Definisi route web
storage/              Folder runtime non-publik
```

## Alur Aplikasi

1. Customer membuka `/`, membuat nomor pesanan, mengisi data sekolah, dan upload logo.
2. Customer diarahkan ke `/orders/{orderNo}/students` untuk upload foto siswa.
3. Customer dapat mencentang `Hapus background` saat upload foto siswa.
4. Admin login di `/admin/login`.
5. Admin mencari nomor pesanan di dashboard dan membuka detail foto.
6. Detail foto memakai lazy loading, preview modal, dan copy image lewat Clipboard API.

## Konfigurasi cPanel

1. Pastikan PHP memakai versi 8.2+ dan ekstensi `pdo_mysql`, `fileinfo`, `gd` aktif dengan dukungan JPEG, PNG, dan WEBP.
2. Import `database/migrations.sql` lewat phpMyAdmin atau MySQL CLI.
3. Copy `.env.example` menjadi `.env`, lalu isi kredensial database cPanel.
4. Pastikan folder ini memiliki `.htaccess` root dan `public/.htaccess`.
5. Document root terbaik adalah `public/`. Jika document root tetap root project, `.htaccess` root akan meneruskan request ke `public/`.
6. Pastikan `public/uploads/logos` dan `public/uploads/photos` writable oleh user web server.

Contoh `.env`:

```env
APP_NAME="School Photo Orders"
APP_ENV=production
APP_TIMEZONE=Asia/Jakarta

DB_HOST=localhost
DB_PORT=3306
DB_DATABASE=nama_database_cpanel
DB_USERNAME=user_database_cpanel
DB_PASSWORD=password_database_cpanel
DB_CHARSET=utf8mb4

MAX_STUDENT_UPLOADS=500
```

## Upload dan Kompresi Foto

- Format yang diterima: JPG, JPEG, PNG, WEBP.
- Ukuran upload mentah maksimal 20 MB per file.
- File di atas 7 MB otomatis dikompresi sebelum disimpan.
- Hasil kompresi disimpan sebagai JPEG dan wajib maksimal 10 MB.
- PNG/WEBP transparan yang dikompresi akan diberi latar putih karena output kompresi adalah JPEG.
- Jika hasil kompresi tetap di atas 10 MB, aplikasi menolak upload dan meminta foto dengan resolusi lebih kecil.
- Opsi `Hapus background` pada foto siswa menyimpan hasil sebagai PNG transparan dan tetap wajib maksimal 10 MB.
- Hapus background memakai deteksi warna dari tepi gambar, sehingga paling cocok untuk background polos.

Rekomendasi PHP cPanel:

```ini
upload_max_filesize=20M
post_max_size=512M
max_file_uploads=5000
memory_limit=512M
max_execution_time=300
```

## Admin Default

Migration membuat admin awal:

```text
Username: admin
Password: admin123
```

Ganti password segera setelah deploy production dengan hash baru dari `password_hash()`.

## Proteksi yang Aktif

- Query database memakai PDO prepared statement.
- Form POST memakai CSRF token.
- Output view memakai escaping `htmlspecialchars`.
- Session admin memakai cookie `HttpOnly`, `SameSite=Lax`, dan `Secure` saat HTTPS terdeteksi.
- Login admin dibatasi 5 percobaan gagal per 15 menit per session.
- Upload divalidasi berdasarkan extension, MIME type, `getimagesize`, ukuran, dan dimensi.
- Folder `public/uploads` menolak eksekusi script lewat `.htaccess`.
- File sensitif seperti `.env`, `.sql`, `.zip`, `.log`, `.ini`, `.bak`, dan dotfile diblokir lewat `.htaccess`.
- Response utama mengirim security headers dasar dan CSP.

## Local Docker

```bash
docker compose up -d --build
```

Import migrasi:

```bash
docker compose exec -T mysql mysql -uroot -proot_secret school_photo_orders < database/migrations.sql
```

Buka `http://localhost:8080`.

## Checklist Production

- Hapus file arsip, backup, dan log publik sebelum upload ke cPanel.
- Jangan simpan kredensial database di `config/database.php`.
- Pastikan HTTPS aktif agar Clipboard API bekerja stabil.
- Pastikan `public/uploads/.htaccess` ikut ter-upload.
- Ganti password admin default.
- Backup database dan folder `public/uploads` secara rutin.
