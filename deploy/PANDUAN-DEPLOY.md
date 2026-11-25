# Deploy Keuangan Pribadi API ke Hostinger

Alamat akhir: **https://cattleyaapp.com/keuangan-api**
(Menumpang di domain utama sebagai folder. Situs lain di cattleyaapp.com tidak diubah.)

## Isi paket

| Folder/file | Tujuan di Hostinger |
|---|---|
| `keuangan-api/` | `domains/cattleyaapp.com/keuangan-api/` (DI LUAR public_html, kode tidak bisa diakses publik) |
| `public_html/keuangan-api/` | `domains/cattleyaapp.com/public_html/keuangan-api/` |
| `schema.sql` | Diimpor lewat phpMyAdmin |

## Langkah

1. **Buat database**: hPanel → Databases → MySQL Databases → buat database + user baru. Catat nama database, user, dan password.
2. **Impor tabel**: hPanel → Databases → phpMyAdmin → pilih database tadi → tab *Import* → pilih `schema.sql` → *Go*.
3. **Upload**: hPanel → File Manager → buka folder `domains/cattleyaapp.com/` → upload `keuangan-api-hostinger.zip` → klik kanan → *Extract* ke folder itu juga.
   Hasilnya harus ada `domains/cattleyaapp.com/keuangan-api/` dan `domains/cattleyaapp.com/public_html/keuangan-api/`.
   Hapus file zip, `schema.sql`, dan panduan ini dari server setelah selesai.
4. **Isi .env**: buka `domains/cattleyaapp.com/keuangan-api/.env` → ganti `ISI_NAMA_DATABASE`, `ISI_USER_DATABASE`, `ISI_PASSWORD_DATABASE`. Simpan.
5. **Versi PHP**: hPanel → Advanced → PHP Configuration → pastikan **PHP 8.3** atau lebih baru.
6. **Cek**: buka https://cattleyaapp.com/keuangan-api di browser → harus muncul
   `{"app":"Keuangan Pribadi API","status":"ok"}`.

## Jika masih dialihkan ke frozen.cattleyaapp.com/react

Domain utama sedang mengalihkan semua alamat. Buka `public_html/.htaccess`, lalu di atas baris aturan redirect tambahkan:

```
RewriteCond %{REQUEST_URI} !^/keuangan-api
```

Jika redirect diatur dari hPanel → Domains → Redirects, ubah menjadi aturan .htaccess seperti di atas, atau beri tahu saya isinya.

## Email lupa password (opsional, bisa menyusul)

hPanel → Emails → buat akun email (misalnya `noreply@cattleyaapp.com`). Lalu di `.env`:

```
MAIL_MAILER=smtp
MAIL_USERNAME=noreply@cattleyaapp.com
MAIL_PASSWORD=password-email
MAIL_FROM_ADDRESS=noreply@cattleyaapp.com
```

## Jadikan akun Anda Premium

Butuh SSH (hPanel → Advanced → SSH Access):

```
cd domains/cattleyaapp.com/keuangan-api
php artisan user:premium USERNAME_ANDA
```

Tanpa SSH: di phpMyAdmin, tabel `users`, ubah kolom `plan` akun Anda menjadi `premium`.

## Update berikutnya

Cukup upload ulang folder yang berubah (biasanya `app/`, `routes/`, `database/migrations/`). Jika ada migrasi baru dan ada SSH: `php artisan migrate --force`.
