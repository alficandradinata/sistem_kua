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

Test: **83 passed** (`php artisan test`).

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
│   │   ├── Reservation.php ......................... 🟢 ⭐ MODEL INTI
│   │   ├── QueueDetail.php ......................... 🟢
│   │   ├── Notification.php ........................ 🟢
│   │   ├── Holiday.php ............................. 🟢
│   │   └── Report.php ............................. 🟢 rekap: periodRange/statsBetween/generateFor
│   └── Http/
│       ├── Controllers/
│       │   ├── PublicController.php ............... 🟢 landing page
│       │   ├── DashboardController.php ........... 🟢 dashboard per peran
│       │   ├── ReservationController.php ........ 🟢 alur reservasi warga
│       │   ├── Petugas/ReservationController.php  🟢 verifikasi (setujui/tolak)
│       │   ├── Petugas/QueueController.php ...... 🟢 papan antrean (panggil/layani)
│       │   ├── Admin/ServiceController.php ..... 🟢 CRUD layanan
│       │   ├── Admin/ScheduleController.php ... 🟢 jam operasional (7 hari sekaligus)
│       │   ├── Admin/ServiceSlotController.php  🟢 slot & kuota antrean
│       │   ├── Admin/HolidayController.php .... 🟢 hari libur (+ peringatan bentrok)
│       │   ├── Admin/UserController.php ....... 🟢 CRUD akun & peran
│       │   ├── Admin/ReportController.php ..... 🟢 laporan: pratinjau, simpan, detail, CSV
│       │   └── Auth/AuthenticatedSessionController.php  🔵 redirect per peran
│       │   └── Auth/RegisteredUserController.php . 🔵 set role=warga + phone
│       ├── Requests/
│       │   ├── Petugas/RejectReservationRequest.php  🟢 validasi alasan tolak
│       │   └── Admin/{Service,Schedule,ServiceSlot,Holiday,User,Report}Request.php  🟢
│       └── Middleware/
│           └── EnsureUserHasRole.php ............. 🟢 alias 'role'
│
├── bootstrap/app.php ................................. 🟡 daftar alias middleware 'role'
├── routes/web.php ................................... 🔵 route landing + dashboard per peran
│
├── database/
│   ├── migrations/
│   │   ├── 2026_09_01_203203..204000_*.php ........ 🟢 8 tabel domain (lihat versi lama di git-less)
│   │   ├── 2026_09_01_204100_add_role_to_users_table.php  🟢 kolom role + phone
│   │   └── 2026_09_01_204200_harden_master_data_constraints.php  🟢 unique & FK master data
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
│   ├── admin/reports/show.blade.php ......... 🟢 rincian per layanan + daftar + unduh CSV
│   ├── components/status-badge.blade.php .... 🟢 komponen badge status
│   ├── components/alert.blade.php ........... 🟢 flash + error validasi
│   ├── components/petugas-tabs.blade.php ... 🟢 sub-navigasi petugas
│   ├── components/admin-tabs.blade.php ..... 🟢 sub-navigasi admin (+ tab Laporan)
│   ├── layouts/navigation.blade.php ......... 🔵 menu per peran
│   └── auth/register.blade.php .............. 🔵 field No. HP
│
├── tests/Feature/RoleRedirectTest.php .......... 🟢 6 test redirect & akses peran
├── tests/Feature/ReservationFlowTest.php ....... 🟢 9 test alur reservasi
├── tests/Feature/Petugas/ReservationVerificationTest.php  🟢 7 test verifikasi
├── tests/Feature/Petugas/QueueBoardTest.php .... 🟢 7 test papan antrean
├── tests/Feature/Admin/MasterDataTest.php ...... 🟢 20 test master data
├── tests/Feature/Admin/ReportTest.php .......... 🟢 10 test laporan & ekspor CSV
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

Alur inti (warga → petugas → admin) sudah lengkap. Sisa yang bisa digarap:

1. **Notifikasi in-app** — `Notification` sudah terisi saat setujui/tolak, tapi warga belum punya
   halaman/lonceng untuk membacanya (`User::appNotifications()`).
2. **Grafik ringkas** di halaman laporan (tren reservasi per hari) — sekarang masih tabel saja.
3. **Penjadwalan laporan otomatis** (mis. buat laporan harian lewat scheduler) — sekarang laporan
   dibuat manual oleh admin.
