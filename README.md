 Absensi Backend & Admin Panel 

Backend service dan web admin untuk **Aplikasi Absensi (Flutter)**.  
Aplikasi Flutter digunakan oleh **user**, sementara project ini handle **API + Web Admin**.

Siap dipakai buat production, scalable, dan gampang diintegrasi.

 Tech Stack

-  Backend: Laravel
-  Client App: Flutter (consume REST API)
-  Database: MySQL / PostgreSQL
-  Authentication: Sanctum (API Token)
-  Web Admin: Blade + Tailwind / Bootstrap

 Arsitektur Singkat

## Pemotongan Cuti Otomatis

Scheduler memeriksa hari kerja sebelumnya setiap pukul 00:10. Satu hari cuti dipotong untuk karyawan aktif berstatus organik yang tidak memiliki data hadir atau pengajuan izin/sakit yang masih pending atau sudah disetujui. Akun dengan sisa cuti nol tidak akan memiliki saldo negatif; tanggal tetap dicatat agar proses tidak menghitungnya berulang.

Admin juga dapat menambah atau mengurangi saldo cuti karyawan organik dari card Sisa Cuti di halaman detail absensi karyawan. Penyesuaian memerlukan alasan, tidak mengubah total cuti yang sudah diambil, dan dicatat di activity log.

Setelah deploy, jalankan migrasi database dan pastikan Laravel Scheduler berjalan setiap menit di server (`php artisan schedule:run`). Hari Sabtu, Minggu, dan tanggal libur yang tercatat tidak dipotong.

## Pengajuan Absensi Tanggal Lampau

Endpoint terautentikasi `POST /api/v1/absensi/pengajuan-lampau` menerima `multipart/form-data` untuk pengajuan maksimal 30 hari ke belakang (tanggal hari ini tidak termasuk). Field wajib: `jenis_pengajuan` (`lupa_keduanya`, `izin`, atau `sakit`), `tanggal` (`YYYY-MM-DD`), `keterangan`, dan satu `file_bukti` gambar (jpg/jpeg/png, maksimal 2 MB). Untuk lupa absen masuk dan pulang sekaligus (`lupa_keduanya`), kirim `jam_masuk` dan `jam_pulang` (`HH:mm`). Untuk izin organik, kirim juga `jenis_izin` (`cuti_tahunan`, `cuti_melahirkan`, `cuti_keguguran`, `cuti_haji`, `cuti_umroh`, `cuti_menikah`, `cuti_khitanan`, `cuti_baptis`, `cuti_meninggal`, `change_off`, atau `unpaid_leave`).

Pengajuan dibuat berstatus `pending` dan diproses lewat alur persetujuan admin yang ada. `lupa_keduanya` hanya dapat diajukan jika belum ada absensi pada tanggal itu. Absen pulang yang terlewat ditangani oleh auto-checkout jam 17:00. Izin/sakit mengikuti pemeriksaan konflik, hari kerja, dan aturan cuti yang sudah berlaku. Foto bukti yang sama ditampilkan pada antrean admin.
