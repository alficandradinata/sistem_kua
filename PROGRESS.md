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
| 17. WhatsApp: auto-reply chat masuk + notifikasi keluar | ✅ selesai (butuh setup Meta) |

Test: **159 passed** (`php artisan test`).

**WhatsApp:** kodenya lengkap & teruji dengan driver `log`. Untuk benar-benar hidup perlu
akun WhatsApp Cloud API + webhook di URL publik HTTPS + `queue:work` berjalan —
langkah lengkapnya ada di `.env.example`.

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
│   │   ├── Reservation.php ......................... 🟢 ⭐ MODEL INTI (+ reject/active, jejak audit)
│   │   ├── QueueDetail.php ......................... 🟢
│   │   ├── Notification.php ........................ 🟡 in-app + teruskan ke WhatsApp
│   │   ├── WhatsAppMessage.php .................... 🟢 riwayat pesan + jendela 24 jam
│   │   ├── AutoReply.php .......................... 🟢 kata kunci → balasan
│   │   ├── Holiday.php ............................. 🟢
│   │   └── Report.php ............................. 🟢 rekap: periodRange/statsBetween/generateFor
│   │                                                    (+ total_rejected & rejection_rate)
│   └── Http/
│       ├── Controllers/
│       │   ├── PublicController.php ............... 🟢 landing page
│       │   ├── DashboardController.php ........... 🟢 dashboard per peran
│       │   ├── ReservationController.php ........ 🟢 alur reservasi warga
│       │   ├── NotificationController.php ...... 🟢 kotak notifikasi in-app
│       │   ├── WhatsAppWebhookController.php ... 🟢 terima chat masuk dari Meta
│       │   ├── Petugas/WhatsAppController.php .. 🟢 inbox koordinasi + balas manual
│       │   ├── Admin/WhatsAppController.php .... 🟢 status kanal + CRUD balasan otomatis
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
├── app/Services/WhatsApp/ ........................... 🟢 infrastruktur kanal WhatsApp
│   ├── WhatsAppGateway.php ...................... 🟢 kontrak pengirim
│   ├── LogGateway.php ........................... 🟢 driver dev/test (tidak kirim)
│   ├── CloudApiGateway.php ...................... 🟢 driver produksi (Meta)
│   └── AutoReplyResolver.php .................... 🟢 penyusun balasan otomatis
├── app/Jobs/SendWhatsAppMessage.php ................. 🟢 kirim via antrean (teks vs template)
├── app/Support/PhoneNumber.php ...................... 🟢 normalisasi nomor ke 62…
├── app/Providers/AppServiceProvider.php ............. 🟡 binding driver WhatsApp
│
├── bootstrap/app.php ................................. 🟡 daftar alias middleware 'role'
├── routes/web.php ................................... 🔵 route landing + dashboard per peran
├── routes/console.php ............................... 🟡 jadwal laporan + pengingat H-1
├── routes/api.php ................................... 🟢 webhook WhatsApp (tanpa CSRF/session)
├── config/whatsapp.php .............................. 🟢 driver, kredensial, template, jeda
│
├── lang/id/{validation,auth,passwords,pagination}.php  🟢 pesan bahasa Indonesia
├── lang/id.json ..................................... 🟢 string UI Breeze
│
├── database/
│   ├── migrations/
│   │   ├── 2026_09_01_203203..204000_*.php ........ 🟢 8 tabel domain (lihat versi lama di git-less)
│   │   ├── 2026_09_01_204100_add_role_to_users_table.php  🟢 kolom role + phone
│   │   ├── 2026_09_01_204200_harden_master_data_constraints.php  🟢 unique & FK master data
│   │   ├── 2026_09_02_090000_add_reminded_at_to_reservations_table.php  🟢 penanda pengingat
│   │   ├── 2026_09_02_100000_create_whatsapp_messages_table.php  🟢 riwayat pesan WA
│   │   ├── 2026_09_02_100100_create_auto_replies_table.php  🟢 kata kunci → balasan
│   │   ├── 2026_09_02_100200_normalize_user_phone_numbers.php  🟢 samakan format nomor
│   │   ├── 2026_09_03_100000_split_rejected_from_cancelled_status.php  🟢 status `rejected`
│   │   │   + kolom `rejection_reason` & `reports.total_rejected` (data lama ikut dipindah)
│   │   └── 2026_09_03_110000_add_audit_trail_to_verification_and_queue.php  🟢 approved_by/
│   │       rejected_by (+ waktunya) & called_by/attended_by — FK nullOnDelete, bukan cascade
│   ├── factories/UserFactory.php .................. 🟡 default role + ->role() state
│   └── seeders/
│       ├── DatabaseSeeder.php .................... 🟡 panggil 5 seeder
│       ├── UserSeeder.php ....................... 🟢
│       ├── ServiceSeeder.php ................... 🟢
│       ├── ScheduleSeeder.php ................. 🟢
│       ├── ServiceSlotSeeder.php ............. 🟢
│       ├── HolidaySeeder.php ................. 🟢
│       └── AutoReplySeeder.php ............... 🟢 balasan WA bawaan
│
├── resources/views/
│   ├── public/queue.blade.php ............... 🟢 layar antrean ruang tunggu (nomor saja)
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
│   ├── admin/whatsapp/index.blade.php ....... 🟢 status kanal + balasan otomatis + riwayat
│   ├── petugas/whatsapp/index.blade.php ..... 🟢 inbox koordinasi (tampilan chat)
│   ├── components/report-trend.blade.php ... 🟢 grafik batang tren harian (tanpa JS)
│   ├── components/status-badge.blade.php .... 🟢 komponen badge status
│   ├── components/alert.blade.php ........... 🟢 flash + error validasi
│   ├── components/petugas-tabs.blade.php ... 🟢 sub-navigasi petugas
│   ├── components/admin-tabs.blade.php ..... 🟢 sub-navigasi admin (+ tab Laporan)
│   ├── layouts/navigation.blade.php ......... 🔵 menu per peran + lonceng notifikasi
│   └── auth/register.blade.php .............. 🔵 field No. HP
│
├── tests/Feature/RoleRedirectTest.php .......... 🟢 6 test redirect & akses peran
├── tests/Feature/ReservationFlowTest.php ....... 🟢 11 test alur reservasi (+ kuota lepas saat ditolak)
├── tests/Feature/PublicQueueDisplayTest.php .... 🟢 5 test layar antrean & penjagaan privasi
├── tests/Feature/WargaDashboardDecisionsTest.php  🟢 5 test panel kabar keputusan
├── tests/Feature/Petugas/ReservationVerificationTest.php  🟢 11 test verifikasi, penolakan & audit
├── tests/Feature/Petugas/QueueBoardTest.php .... 🟢 9 test papan antrean (+ jejak petugas loket)
├── tests/Feature/Admin/MasterDataTest.php ...... 🟢 22 test master data (+ akun berjejak audit)
├── tests/Feature/Admin/ReportTest.php .......... 🟢 11 test laporan & ekspor CSV (+ pisah ditolak/batal)
├── tests/Feature/Admin/GenerateReportCommandTest.php  🟢 7 test perintah & tren harian
├── tests/Feature/NotificationTest.php .......... 🟢 9 test notifikasi in-app
├── tests/Feature/TimezoneTest.php .............. 🟢 6 test pengunci zona WIB
├── tests/Feature/ReservationReminderTest.php ... 🟢 6 test pengingat H-1
├── tests/Feature/LocalizationTest.php .......... 🟢 7 test pesan bahasa Indonesia
├── tests/Unit/PhoneNumberTest.php .............. 🟢 10 test normalisasi nomor
├── tests/Feature/WhatsApp/WebhookTest.php ...... 🟢 9 test webhook & auto-reply
├── tests/Feature/WhatsApp/OutboundTest.php ..... 🟢 7 test notifikasi keluar
├── tests/Feature/WhatsApp/PanelTest.php ........ 🟢 14 test panel admin & inbox petugas
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

Baru selesai:
- **Pisah `rejected` dari `cancelled`** — laporan KUA bisa membedakan berkas yang ditolak
  petugas dari reservasi yang dibatalkan warga.
- **Jejak audit** — setiap persetujuan, penolakan, dan pemanggilan antrean tercatat
  pelakunya; akun petugas yang punya jejak tidak bisa dihapus.
- **Paginasi dashboard warga** — dulu mengambil semua riwayat sekaligus, kini `paginate(10)`.
- **Layar antrean publik** `/antrean` — nomor saja, tanpa nama warga & jenis layanan
  (keputusan privasi, dikunci test). Auto-refresh 15 detik tanpa JS.
- **Rombak tampilan** — palet hijau institusional + aksen emas, netral hangat `stone`,
  Plus Jakarta Sans + Lora. Token di `tailwind.config.js`.
- **Panel "Kabar Terbaru"** di dashboard warga — keputusan disetujui/ditolak tampil
  langsung berikut nomor antrean / alasan penolakan, tidak cuma di lonceng.

Antre berikutnya, urut prioritas:

1. **Tiket antrean bisa dicetak** — belum ada satu pun halaman cetak.
2. **Ubah jadwal (reschedule)** — sekarang warga harus batal lalu pesan ulang.
3. **WhatsApp lanjutan** — pesan gambar/lokasi (sekarang diabaikan), status pengiriman
   (`statuses[]` dari webhook), penugasan percakapan ke petugas tertentu.
4. **Verifikasi email** — `User` belum implement `MustVerifyEmail`, jadi middleware `verified`
   di `routes/web.php` sekarang **tidak berefek sama sekali** (kelihatan aman padahal tidak).
   Perlu SMTP dulu; kalau belum ada, minimal copot `verified` dari route group.

Rapi-rapi kecil yang belum dikerjakan: `AutoReplySeeder` belum tercatat di daftar seeder
CLAUDE.md; judul halaman semua sama (`layouts/app.blade.php` cuma pakai `config('app.name')`);
belum ada favicon; `resources/views/welcome.blade.php` bawaan Laravel masih menganggur.

(Sudah ada, jangan dikerjakan ulang: filter tanggal papan antrean — `QueueController::index`
menerima `?date=` dan view-nya punya date picker.)
