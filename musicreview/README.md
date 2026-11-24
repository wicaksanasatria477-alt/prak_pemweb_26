# MusicReview (PHP + MySQL + Bootstrap 5)

## Cara menjalankan (XAMPP)
1. Salin folder `musicreview` ke `C:\xampp\htdocs\`.
2. Nyalakan **Apache** dan **MySQL** di XAMPP.
3. Buka phpMyAdmin -> tab **Import** -> pilih `database/musicreview.sql` -> Go.
   (file ini sudah berisi `CREATE DATABASE musicreview`)
4. Buka http://localhost/musicreview/

Kalau user/password MySQL berbeda, ubah di `koneksi.php`.

## Akun contoh (password semua: 123456)
| Role  | Email           |
|-------|-----------------|
| admin | admin@mail.com  |
| user  | budi@mail.com   |
| user  | sinta@mail.com  |

## Struktur file
- `koneksi.php`  : koneksi database + fungsi bantu (query, upload, pagination, cek login)
- `index.php`, `artists.php`, `detail_artist.php`, `detail_album.php`, `detail_lagu.php`
- `login.php`, `register.php`, `logout.php`, `profil.php`, `favorit.php`
- `admin/` : `index.php` (songs), `albums.php`, `artists.php`, `genres.php`, `reviews.php`
- `includes/` : header & footer, `css/style.css`, `js/script.js`
- `uploads/` : foto profil, foto artist, cover album (otomatis terisi saat upload)
