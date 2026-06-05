# Audit Project

Tanggal audit: 2026-06-04

## Ringkasan

Aplikasi adalah PHP native MVC kecil yang langsung bisa berjalan di cPanel. Area paling penting adalah konfigurasi produksi, upload file publik, autentikasi admin, dan performa pencarian ketika jumlah foto makin besar.

## Temuan dan Perbaikan

| Area | Risiko | Status |
| --- | --- | --- |
| Kredensial database hardcoded di `config/database.php` | Password produksi bisa bocor saat file tersalin/terunduh | Diperbaiki. Nilai default sensitif dihapus dan wajib memakai `.env`. |
| Upload gambar publik | Risiko file script tersimpan di folder publik atau gambar rusak/resource abuse | Diperbaiki. Validasi extension, MIME, dimensi, ukuran, kompresi >7 MB, dan `.htaccess` anti-script di `public/uploads`. |
| Foto besar memperlambat UI | Detail admin dapat memuat file foto sangat besar | Diperbaiki sebagian. Upload >7 MB dikompresi menjadi JPEG maksimal 10 MB dan lazy loading tetap aktif. |
| Kebutuhan hapus background | Butuh proses gambar tanpa layanan eksternal agar cocok untuk cPanel | Ditambahkan. Ada opsi `Hapus background` untuk foto siswa, output PNG transparan maksimal 10 MB. |
| Login admin tanpa pembatasan | Brute force sederhana pada `/admin/login` | Diperbaiki. Ada lock 15 menit setelah 5 percobaan gagal per session. |
| Query count foto | Subquery sebelumnya menghitung seluruh tabel `students` sebelum filter | Diperbaiki. Count dihitung per order yang sedang dicari/dibuka. |
| Bootstrap Icons dan Bootstrap JS | Request eksternal tambahan dan JS lebih berat dari kebutuhan aplikasi | Diperbaiki. Dihapus, diganti JavaScript lokal kecil. |
| File server ikut tersalin | `VANDEL.zip` dan `public/error_log` tidak dipakai aplikasi | Dihapus. Pattern log/zip juga ditambahkan ke ignore file. |
| Code database lama | `MysqliConnection` dan `MysqliStatement` tidak dipakai | Dihapus. Aplikasi konsisten memakai PDO. |

## Risiko Tersisa

- Rate limit login masih berbasis session, bukan IP atau database. Untuk produksi ramai, tambahkan proteksi di level cPanel/WAF/Cloudflare.
- Foto hasil upload tetap publik karena kebutuhan aplikasi. Jangan upload data yang harus private tanpa menambah kontrol akses file.
- Hapus background memakai algoritma sederhana berbasis warna tepi gambar. Untuk background ramai atau detail rambut halus, hasilnya tidak seakurat model AI khusus.
- CSP masih mengizinkan Bootstrap CSS dari CDN. Jika ingin sepenuhnya self-hosted, simpan CSS Bootstrap lokal atau ganti seluruh utility Bootstrap dengan CSS lokal.
- Tidak ada fitur hapus pesanan/foto dari admin. Retensi data harus dikelola manual atau dibuatkan fitur khusus.

## File yang Dihapus

- `app/Core/MysqliConnection.php`
- `app/Core/MysqliStatement.php`
- `VANDEL.zip`
- `public/error_log`

## Rekomendasi Lanjutan

- Tambahkan halaman ubah password admin.
- Tambahkan pagination atau virtualized grid jika jumlah foto per pesanan sangat besar.
- Tambahkan audit log admin untuk akses detail pesanan.
- Pertimbangkan thumbnail terpisah jika ingin detail admin lebih cepat lagi untuk ribuan foto.
