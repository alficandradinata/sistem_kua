# PROGRESS — Sistem Reservasi Antrean KUA

Peta file yang **kita buat / ubah**, terpisah dari bawaan Laravel/Breeze.
File kita diberi penanda `[SISTEM KUA]` di dalam kodenya (cari teks itu di editor).

Legenda: 🟢 kita buat baru · 🟡 kita ubah dari bawaan · 🔵 kita ubah dari Breeze · ⚪ bawaan (tak disentuh)

---

## Status keseluruhan

| Tahap | Keadaan |
|---|---|
| 1. Migration (8 tabel domain + kolom role) | ✅ selesai |
| 2. Eloquent Model (9 model) | ✅ selesai & terverifikasi |
| 3. Seeder data awal | ✅ 5 seeder (user, service, schedule, slot, holiday) |
| 4. Auth (Breeze Blade) + kolom `role` | ✅ selesai |
| 5. Landing page publik + dashboard per peran | ✅ kerangka (angka + placeholder) |
| 6. Alur reservasi warga (pilih layanan → slot → buat → batal) | ✅ selesai |
| 7. Panel petugas (verifikasi + papan antrean) | ✅ selesai |
| 8. Panel admin master data (CRUD layanan/jadwal/slot/libur/pengguna) | ✅ selesai |
| 9. Laporan rekap (harian/mingguan/bulanan + ekspor CSV) | ✅ selesai |
| 10. Notifikasi in-app warga (lonceng + kotak notifikasi) | ✅ selesai |
| 11. Grafik tren harian di laporan | ✅ selesai |
| 12. Laporan otomatis terjadwal (`laporan:buat` + scheduler) | ✅ selesai |
| 13. Zona waktu WIB + berkas persiapan deploy | ✅ selesai |
| 14. Pengingat H-1 (`pengingat:reservasi`) | ✅ selesai |
| 15. Ekspor laporan PDF (dompdf) | ✅ selesai |
| 16. Bahasa Indonesia (validasi, auth, UI Breeze) | ✅ selesai |

Test: **120 passed** (`php artisan test`).

**Siap deploy?** Kodenya sudah; setelan servernya belum. Checklist lengkap ada di
bagian bawah `.env.example` (APP_ENV/APP_DEBUG, user DB non-root, SMTP, cron
`schedule:run`, `queue:work`, `npm run build`, izin tulis storage).

**Akun demo** (password semua: `password`):
`admin@kua.test` · `petugas@kua.test` · `warga@kua.test`

**Jalankan:** MySQL XAMPP hidup → `php artisan migrate:fresh --seed` → `npm run build` →
`php artisan serve` → buka http://127.0.0.1:8000

---

## File kita

```
sistem_kua/
├── CLAUDE.md .......................................... 🟢 konteks & aturan Claude Code
├── PROGRESS.md ....................................... 🟢 file ini
│
├── app/
│   ├── Models/
│   │   ├── User.php ................................. 🟡 relasi + kolom role + helper peran
│   │   ├── Service.php ............................. 🟢
│   │   ├── Schedule.php ............................ 🟢
│   │   ├── ServiceSlot.php ......................... 🟢 logika "slot penuh"
│   │   ├── Reservation.php ......................... 🟢 ⭐ MODEL INTI (+ sendReminder)
│   │   ├── QueueDetail.php ......................... 🟢
│   │   ├── Notification.php ........................ 🟢
│   │   ├── Holiday.php ............................. 🟢
│   │   └── Report.php ............................. 🟢 rekap: periodRange/statsBetween/generateFor
│   └── Http/
│       ├── Controllers/
│       │   ├── PublicController.php ............... 🟢 landing page
│       │   ├── DashboardController.php ........... 🟢 dashboard per peran
│       │   ├── ReservationController.php ........ 🟢 alur reservasi warga
│       │   ├── NotificationController.php ...... 🟢 kotak notifikasi in-app
│       │   ├── Petugas/ReservationController.php  🟢 verifikasi (setujui/tolak)
│       │   ├── Petugas/QueueController.php ...... 🟢 papan antrean (panggil/layani)
│       │   ├── Admin/ServiceController.php ..... 🟢 CRUD layanan
│       │   ├── Admin/ScheduleController.php ... 🟢 jam operasional (7 hari sekaligus)
│       │   ├── Admin/ServiceSlotController.php  🟢 slot & kuota antrean
│       │   ├── Admin/HolidayController.php .... 🟢 hari libur (+ peringatan bentrok)
│       │   ├── Admin/UserController.php ....... 🟢 CRUD akun & peran
│       │   ├── Admin/ReportController.php ..... 🟢 laporan: pratinjau, simpan, detail, CSV, PDF
│       │   └── Auth/AuthenticatedSessionController.php  🔵 redirect per peran
│       │   └── Auth/RegisteredUserController.php . 🔵 set role=warga + phone
│       ├── Requests/
│       │   ├── Petugas/RejectReservationRequest.php  🟢 validasi alasan tolak
│       │   └── Admin/{Service,Schedule,ServiceSlot,Holiday,User,Report}Request.php  🟢
│       └── Middleware/
│           └── EnsureUserHasRole.php ............. 🟢 alias 'role'
│
├── app/Console/Commands/
│   ├── GenerateReport.php ....................... 🟢 `laporan:buat` (dipakai scheduler)
│   └── SendReservationReminders.php ............ 🟢 `pengingat:reservasi` (H-1)
│
├── bootstrap/app.php ................................. 🟡 daftar alias middleware 'role'
├── routes/web.php ................................... 🔵 route landing + dashboard per peran
├── routes/console.php ............................... 🟡 jadwal laporan + pengingat H-1
│
├── lang/id/{validation,auth,passwords,pagination}.php  🟢 pesan bahasa Indonesia
├── lang/id.json ..................................... 🟢 string UI Breeze
│
├── database/
│   ├── migrations/
│   │   ├── 2026_09_01_203203..204000_*.php ........ 🟢 8 tabel domain (lihat versi lama di git-less)
│   │   ├── 2026_09_01_204100_add_role_to_users_table.php  🟢 kolom role + phone
│   │   ├── 2026_09_01_204200_harden_master_data_constraints.php  🟢 unique & FK master data
│   │   └── 2026_09_02_090000_add_reminded_at_to_reservations_table.php  🟢 penanda pengingat
│   ├── factories/UserFactory.php .................. 🟡 default role + ->role() state
│   └── seeders/
│       ├── DatabaseSeeder.php .................... 🟡 panggil 5 seeder
│       ├── UserSeeder.php ....................... 🟢
│       ├── ServiceSeeder.php ................... 🟢
│       ├── ScheduleSeeder.php ................. 🟢
│       ├── ServiceSlotSeeder.php ............. 🟢
│       └── HolidaySeeder.php ................. 🟢
│
├── resources/views/
│   ├── public/home.blade.php .................... 🟢 landing page
│   ├── dashboard.blade.php ..................... 🔵 dashboard warga + Reservasi Saya
│   ├── reservations/create.blade.php .......... 🟢 form 2 langkah (layanan+tanggal → slot)
│   ├── reservations/show.blade.php ............ 🟢 detail + tombol batal
│   ├── petugas/dashboard.blade.php ............ 🟢 ringkasan + daftar menunggu
│   ├── petugas/reservations/index.blade.php ... 🟢 verifikasi + filter + pagination
│   ├── petugas/queues/index.blade.php ........ 🟢 papan antrean harian
│   ├── admin/dashboard.blade.php ............. 🟢 ringkasan + pintasan master data
│   ├── admin/services/{index,form}.blade.php . 🟢 CRUD layanan
│   ├── admin/schedules/index.blade.php ...... 🟢 jam operasional 7 hari
│   ├── admin/slots/index.blade.php .......... 🟢 slot & kuota
│   ├── admin/holidays/index.blade.php ....... 🟢 hari libur
│   ├── admin/users/{index,form}.blade.php ... 🟢 CRUD akun
│   ├── admin/reports/index.blade.php ........ 🟢 pratinjau periode + laporan tersimpan
│   ├── admin/reports/show.blade.php ......... 🟢 tren + rincian + daftar + unduh CSV/PDF
│   ├── admin/reports/pdf.blade.php .......... 🟢 template PDF (dompdf, tanpa Tailwind)
│   ├── notifications/index.blade.php ........ 🟢 kotak notifikasi warga/petugas/admin
│   ├── components/report-trend.blade.php ... 🟢 grafik batang tren harian (tanpa JS)
│   ├── components/status-badge.blade.php .... 🟢 komponen badge status
│   ├── components/alert.blade.php ........... 🟢 flash + error validasi
│   ├── components/petugas-tabs.blade.php ... 🟢 sub-navigasi petugas
│   ├── components/admin-tabs.blade.php ..... 🟢 sub-navigasi admin (+ tab Laporan)
│   ├── layouts/navigation.blade.php ......... 🔵 menu per peran + lonceng notifikasi
│   └── auth/register.blade.php .............. 🔵 field No. HP
│
├── tests/Feature/RoleRedirectTest.php .......... 🟢 6 test redirect & akses peran
├── tests/Feature/ReservationFlowTest.php ....... 🟢 9 test alur reservasi
├── tests/Feature/Petugas/ReservationVerificationTest.php  🟢 7 test verifikasi
├── tests/Feature/Petugas/QueueBoardTest.php .... 🟢 7 test papan antrean
├── tests/Feature/Admin/MasterDataTest.php ...... 🟢 20 test master data
├── tests/Feature/Admin/ReportTest.php .......... 🟢 10 test laporan & ekspor CSV
├── tests/Feature/Admin/GenerateReportCommandTest.php  🟢 7 test perintah & tren harian
├── tests/Feature/NotificationTest.php .......... 🟢 9 test notifikasi in-app
├── tests/Feature/TimezoneTest.php .............. 🟢 6 test pengunci zona WIB
├── tests/Feature/ReservationReminderTest.php ... 🟢 6 test pengingat H-1
├── tests/Feature/LocalizationTest.php .......... 🟢 7 test pesan bahasa Indonesia
│
├── config/app.php ............................... 🟡 timezone Asia/Jakarta + locale id
├── .env.example ................................. 🟡 MySQL + APP_TIMEZONE + checklist deploy
├── composer.json ................................ 🟡 + barryvdh/laravel-dompdf (ekspor PDF)
│
└── (selain di atas) ........................... ⚪ bawaan Laravel 12 / Breeze
    routes/auth.php, app/Http/Controllers/Auth/* lainnya, resources/views/auth/*,
    resources/views/profile/*, resources/views/components/*,
    resources/views/layouts/{app,guest}.blade.php, dst.
```

---

## Cara cepat menemukan kode kita

- Cari teks **`[SISTEM KUA]`** di editor (Ctrl+Shift+F).
- Folder utama: `app/Models/`, `app/Http/Controllers/` (kecuali `Auth/`),
  `database/seeders/`, `resources/views/{public,petugas,admin}/`.

---

## Berikutnya (ronde selanjutnya)

Semua rencana ronde sebelumnya sudah dikerjakan. Kandidat berikutnya:

1. **Notifikasi ringkas di dashboard warga** — mis. 3 notifikasi terakhir langsung terlihat
   tanpa membuka halaman `/notifikasi`.
2. **Verifikasi email** — `User` belum implement `MustVerifyEmail`, jadi middleware `verified`
   di `routes/web.php` sekarang tidak berefek. Perlu SMTP dulu.
3. **Terjemahan sisa UI** — teks di view kita ditulis langsung dalam bahasa Indonesia
   (tidak lewat `__()`), jadi sudah Indonesia; yang lewat `__()` ada di `lang/id.json`.

(Sudah ada, jangan dikerjakan ulang: filter tanggal papan antrean — `QueueController::index`
menerima `?date=` dan view-nya punya date picker.)
