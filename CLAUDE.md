# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project

Sistem Informasi Reservasi Antrean KUA (Kantor Urusan Agama) — warga memesan slot antrean
untuk layanan KUA (pendaftaran nikah, rujuk, legalisir, konsultasi), petugas menyetujui dan
memanggil antrean, admin melihat rekap. Bahasa domain & UI: Indonesia.

**Status:** alur inti lengkap — warga reservasi → petugas verifikasi & panggil antrean →
admin kelola master data & rekap laporan.
- Laravel Breeze (Blade) terpasang.
- Kolom `users.role` (`warga`/`petugas`/`admin`) + helper di `User`, middleware alias `role`,
  redirect pasca-login per peran (`User::homeRoute()`).
- Landing page publik `/` (`PublicController`); dashboard per peran (`DashboardController` →
  `/dashboard`, `/petugas`, `/admin`).
- Alur reservasi warga (`ReservationController`, route `reservasi/*`): form 2 langkah
  (layanan+tanggal → slot), store dgn cek `Holiday::isHoliday` / `Schedule::isOpenOn` /
  `ServiceSlot::isAvailable` / anti double-book, show, cancel.
- Seeder: `UserSeeder` (admin@kua.test / petugas@kua.test / warga@kua.test, password `password`),
  `ServiceSeeder`, `ScheduleSeeder` (Sen–Jum aktif), `ServiceSlotSeeder`, `HolidaySeeder`.

- Panel petugas (`app/Http/Controllers/Petugas/`, route `petugas/*`): verifikasi reservasi
  (setujui → `Reservation::approveAndIssueQueue()` menerbitkan `QueueDetail` dalam transaksi +
  notifikasi; tolak → `Reservation::reject($reason)` via `RejectReservationRequest` — statusnya
  `rejected`, alasannya masuk kolom `rejection_reason`), dan papan antrean harian
  (panggil / panggil-berikutnya / selesai dilayani).

- Panel admin (`app/Http/Controllers/Admin/`, route `admin/*`, middleware `role:admin`):
  CRUD layanan, jam operasional (7 hari sekaligus), slot & kuota, hari libur, akun pengguna.
- Laporan (`Admin\ReportController`, route `admin/laporan*`): pilih jenis (harian/mingguan/
  bulanan) + tanggal acuan → pratinjau angka → simpan jadi `Report` lewat
  `Report::generateFor()` (satu periode = satu baris, digenerate ulang = diperbarui) →
  halaman rincian per layanan + daftar reservasi + ekspor CSV.

- Notifikasi in-app (`NotificationController`, route `notifikasi*`, semua peran): lonceng +
  badge di navbar (`User::unreadNotificationCount()`), kotak notifikasi dengan filter
  belum dibaca, tandai dibaca (satu/semua), hapus.
- Grafik tren harian di detail laporan (`x-report-trend`) — batang bertumpuk dirender
  server-side, tanpa library JS; hanya muncul kalau periode lebih dari sehari.
- Laporan otomatis: perintah `php artisan laporan:buat --type=... --date=...`
  (`App\Console\Commands\GenerateReport`), dijadwalkan di `routes/console.php`
  (harian 23:55, mingguan Minggu 23:57, bulanan hari terakhir 23:59).
- Ekspor laporan **PDF** (`barryvdh/laravel-dompdf`, template `admin/reports/pdf.blade.php`)
  di samping CSV. Template PDF pakai CSS sederhana + tabel — dompdf tidak mendukung
  flexbox/grid, jadi jangan pakai kelas Tailwind di situ.
- Pengingat H-1: `php artisan pengingat:reservasi` (`SendReservationReminders`), dijadwalkan
  17:00 tiap hari. `Reservation::sendReminder()` menandai kolom `reminded_at` supaya tidak
  mengirim dua kali.
- **Bahasa Indonesia**: `lang/id/{validation,auth,passwords,pagination}.php` + `lang/id.json`
  untuk string UI Breeze. `config/app.php` default locale `id` (fallback `en`).
  Pesan validasi ditulis agar `:attribute` tidak pernah di awal kalimat — kalau di awal,
  hurufnya jadi kecil ("email sudah digunakan"). Nama kolom ada di bagian `attributes`.

- **WhatsApp** (`config/whatsapp.php`, `app/Services/WhatsApp/`, route `api/whatsapp/webhook`):
  chat masuk dibalas otomatis (kata kunci admin → menu angka 1/2/3 → sapaan), dan notifikasi
  sistem ikut dikirim ke WA. Panel `admin/whatsapp` (status + CRUD balasan + kirim uji) dan
  inbox koordinasi `petugas/whatsapp` (baca & balas manual).

**Belum ada:** notifikasi ringkas di dashboard warga, verifikasi email aktif (User belum
implement `MustVerifyEmail`), SMTP produksi.

## Stack

- Laravel 12, PHP 8.2
- MySQL untuk dev (database `sistem_kua`, via XAMPP di `C:\xampp`)
- Test pakai SQLite in-memory (lihat `phpunit.xml`)
- Tailwind CSS **v3** (Breeze menurunkan dari v4 saat install) — `tailwind.config.js` +
  `postcss.config.js`. `content` sudah termasuk `./app/**/*.php` supaya kelas di accessor
  (mis. `Reservation::status_color`) ikut ter-scan.
- Laravel Breeze (Blade + Alpine). **`npm run build` WAJIB setelah ubah `.blade.php` atau
  string kelas Tailwind di PHP.**
- Struktur skeleton Laravel 12 ramping: tidak ada `app/Http/Kernel.php` / `app/Console/Kernel.php`;
  middleware, exception, routing didaftarkan di `bootstrap/app.php`.

## Perintah

```bash
# MySQL harus jalan dulu — SESSION_DRIVER=database, jadi SEMUA halaman butuh DB.
# Error "SQLSTATE[HY000] [2002] ... refused" => nyalakan MySQL lewat XAMPP Control Panel
# atau: /c/xampp/mysql/bin/mysqld.exe --defaults-file=/c/xampp/mysql/bin/my.ini --standalone

composer dev            # serve + queue + pail + vite sekaligus
php artisan serve --host=0.0.0.0 --port=8000

php artisan migrate:fresh --seed      # reset + isi data awal (DB dev boleh dihapus)
php artisan tinker

php artisan test                       # semua test (SQLite in-memory)
php artisan test --filter=NamaTest     # satu test / method
./vendor/bin/pint                      # format kode (jalankan sebelum selesai)

php artisan laporan:buat --type=weekly  # buat laporan manual (dipakai scheduler)
php artisan pengingat:reservasi        # kirim pengingat H-1 manual
php artisan schedule:list              # cek jadwal laporan otomatis
php artisan schedule:work              # jalankan scheduler saat dev

npm run build                          # WAJIB setelah ubah .blade.php
npm run dev                            # vite HMR
```

## Arsitektur

**Semua logika bisnis ada di method model** (`app/Models/`), bukan service class. Controller tipis.

**Satu pengecualian:** `app/Services/WhatsApp/` berisi *infrastruktur* (HTTP ke Meta) dan
penyusun teks balasan, bukan aturan domain — `WhatsAppGateway` + `LogGateway`/`CloudApiGateway`
(dipilih di `AppServiceProvider`) dan `AutoReplyResolver`. Keputusan domainnya tetap di model
(`Reservation`, `Schedule`, `AutoReply`).

- **`Reservation`** — pusat data. `belongsTo` User & Service, `hasOne` QueueDetail. Status via
  method `approve()` / `complete()` / `cancel()` / `reject($reason)`; guard `canBeCancelled()`.
  Scope: `pending()`, `active()`, `today()`, `upcoming()`, `forUser()`, `forDate()`, dll.
  Relasi audit `approvedBy()` / `rejectedBy()` (FK eksplisit — `user_id` itu milik warga).
- **`ServiceSlot`** — `remainingQuota(date)` / `isAvailable(date)` hitung sisa kuota,
  mengecualikan reservasi `cancelled` **dan** `rejected` (lewat scope `active()`).
  Inti validasi "slot penuh".
- **`Schedule`** — `Schedule::isOpenOn(date)` cek KUA buka (0=Minggu..6=Sabtu). Konstanta `DAYS`.
- **`Holiday`** — `Holiday::isHoliday(date)` blokir tanggal libur.
- **`Notification`** — `Notification::send(userId, message, type)`; scope `unread()`,
  `forUser()`, `latestFirst()`; accessor `time_ago`; `markAsRead()`.
  **Sekaligus meneruskan pesan ke WhatsApp** lewat `forwardToWhatsApp()` bila user punya
  `phone` — jadi jangan menambah pengiriman WA manual di pemanggilnya.
- **`WhatsAppMessage`** — riwayat masuk/keluar; `withinSessionWindow($nomor)` menentukan
  boleh-tidaknya kirim teks bebas (jendela 24 jam Cloud API). Tabelnya `whatsapp_messages`
  (ditulis eksplisit di `$table`, karena Laravel menebaknya `whats_app_messages`).
- **`AutoReply`** — kata kunci → balasan; `AutoReply::match($teks)`.
- **Nomor HP selalu ternormalisasi 62…** lewat `App\Support\PhoneNumber` + mutator
  `User::setPhoneAttribute`. Cari pemilik nomor dengan `User::findByPhone()`.
- **`Report`** — rekap agregat. `periodRange($type,$date)` menormalkan tanggal ke awal periode,
  `statsBetween()` menghitung per status, `generateFor()` membuat/memperbarui satu baris laporan;
  `serviceBreakdown()` / `reservationsQuery()` / `dailyTrend()` untuk halaman rincian & grafik.
  Accessor `period_label`, `completion_rate`, `cancellation_rate`, `total_pending`.
- **`User`** — konstanta `ROLE_*`; `isWarga()/isPetugas()/isAdmin()/hasRole()/homeRoute()`;
  scope `role()`. Relasi `reservations()`, `appNotifications()`, `reports()`.

**Alur reservasi (dituju):** warga pilih layanan → cek `Schedule::isOpenOn` + `Holiday::isHoliday`
+ `ServiceSlot::isAvailable` → `Reservation` (pending) → petugas `approve()` → generate
`QueueDetail` (`QueueDetail::generateNumber(date)`) → `markAsCalled()` → `markAsAttended()` →
`Report` mengagregasi.

## Konvensi (WAJIB — keputusan desain yang sudah dibuat)

- **Casts** pakai method `protected function casts(): array`, bukan properti `$casts`.
- **Kolom TIME jangan di-cast** ke `datetime`. `reservation_time`, `slot_start_time`,
  `open_time`, `close_time` dibiarkan string `H:i:s`; format via accessor.
- **Zona waktu `Asia/Jakarta`** (WIB) — `config/app.php` sengaja tidak lagi default UTC.
  `today()`/`now()` dipakai papan antrean, `QueueDetail::generateNumber`, validasi
  `after:today`, dan periode laporan; dengan UTC semuanya meleset sehari tiap 00:00–07:00 WIB.
  Dikunci `tests/Feature/TimezoneTest.php` — jangan menulis test tanggal yang relatif ke
  `today()` di situ, pakai tanggal literal supaya bedanya ketahuan.
- **Tanggal Indonesia:** `Carbon::parse($x)->locale('id')->translatedFormat('j F Y')` — selalu
  `->locale('id')` eksplisit.
- **Query kolom yang di-cast `date`** (`reservation_date`, `holiday_date`, `report_date`) selalu
  pakai `whereDate`, bukan `where(...)` persis: Laravel menulisnya sebagai `Y-m-d H:i:s`, jadi di
  SQLite (test) nilainya membawa jam. Lihat `Reservation::scopeBetweenDates`, `HolidayRequest`.
- **`User::appNotifications()`**, BUKAN `notifications()` (milik trait `Notifiable`).
- **`Report::generatedBy()`** foreign key eksplisit `'generated_by'`.
- **String status/tipe/peran = konstanta model** (`Reservation::STATUS_*`, `User::ROLE_*`,
  `Notification::TYPE_*`, `Report::TYPE_*`). Jangan tulis literal.
- **Jejak audit wajib ikut terisi.** Setiap keputusan petugas mencatat pelakunya:
  `reservations.approved_by/approved_at` & `rejected_by/rejected_at`,
  `queue_details.called_by` & `attended_by`. Diisi otomatis oleh method model
  (`approve()`, `reject()`, `markAsCalled()`, `markAsAttended()`) yang default-nya
  `auth()->id()` — di test lewat argumen `$petugasId`. FK-nya **`nullOnDelete()`, bukan
  cascade**: menghapus akun petugas tidak boleh menghapus riwayat warga.
  `User::hasVerificationHistory()` menahan penghapusan akun yang punya jejak.
  Accessor terbaca: `Reservation::verification_log`, `QueueDetail::handled_by_label`.
- **`cancelled` ≠ `rejected`.** `cancelled` = warga membatalkan sendiri, `rejected` = petugas
  menolak berkas (alasan di `rejection_reason`, bukan menumpang `notes` milik warga). Laporan
  menghitungnya terpisah (`total_cancelled` / `total_rejected`). Untuk "reservasi yang masih
  memakai kuota" **pakai scope `Reservation::active()`** — jangan pernah menulis
  `where('status', '!=', STATUS_CANCELLED)`, itu melewatkan yang ditolak sehingga slotnya
  hangus. Daftarnya ada di `Reservation::STATUSES_INACTIVE`.
- **Peran**: gate route dgn middleware `role:admin` / `role:petugas,admin`.
  `User::factory()->role($r)` untuk test. Registrasi publik selalu `warga`.
- **Penanda `[SISTEM KUA]`** di setiap file yang kita buat/ubah (bukan file bawaan Breeze murni).
  Peta lengkap di `PROGRESS.md`.
- **Migration** bertanggal `2026_09_01_*`; urutan penting. FK `->constrained()->onDelete('cascade')`.

## Verifikasi cepat

```bash
php artisan migrate:fresh --seed && php artisan test && npm run build
```
