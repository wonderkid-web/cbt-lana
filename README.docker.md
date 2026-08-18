# Menjalankan CBT SMK Putra Anda Binjai dengan Docker

## Kebutuhan
Docker Desktop (sudah termasuk `docker compose`).

## Cara menjalankan

```bash
docker compose up -d --build
```

Tunggu ±30 detik saat pertama kali (database di-import otomatis dari
`database/db_cbt_binjai.sql`).

| Layanan     | Alamat                                        |
|-------------|-----------------------------------------------|
| Aplikasi CBT| http://localhost:8080                         |
| phpMyAdmin  | http://localhost:8081  (user `root` / `root`) |
| MySQL/MariaDB | `localhost:3307` (user `root` / `root`)     |

## Mengubah port

Kalau port bentrok dengan aplikasi lain (`port is already allocated`),
ubah nilainya di file `.env` — **tidak perlu menyentuh docker-compose.yml**:

```
WEB_PORT=8080   # alamat aplikasi CBT
PMA_PORT=8081   # phpMyAdmin
DB_PORT=3307    # MySQL dari HeidiSQL/DBeaver
```

Lalu jalankan lagi:

```bash
docker compose up -d
```

Container yang portnya berubah akan dibuat ulang otomatis dan port lama dilepas.
Pastikan ketiga port tersebut **berbeda satu sama lain**.

Melihat port yang sedang dipakai:

```bash
docker compose ps
```

## Perintah lain

```bash
docker compose logs -f web
```

```bash
docker compose down
```

Menghapus semua data database (import ulang dari dump saat start berikutnya):

```bash
docker compose down -v
```

## Catatan

- Folder proyek di-mount ke dalam container, jadi setiap perubahan file PHP
  langsung terlihat di browser tanpa perlu rebuild.
- Dump SQL **hanya** di-import saat volume database masih kosong. Untuk
  memuat ulang dump, jalankan `docker compose down -v` lalu `up` lagi.
- Import dump secara manual:

```bash
docker compose exec -T db mysql -uroot -proot db_cbt_binjai < database/db_cbt_binjai.sql
```

- Kredensial database di dalam Docker diambil dari environment variable
  (`DB_HOST`, `DB_USER`, `DB_PASS`, `DB_NAME`) di `docker-compose.yml`.
  Di luar Docker (XAMPP), `config/database.php` tetap memakai default lama
  (`localhost` / `root` / password kosong).
