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

Admin juga dapat menambah atau mengurangi saldo cuti karyawan organik dari aksi Kelola Cuti di rekap absensi. Penyesuaian memerlukan alasan, tidak mengubah total cuti yang sudah diambil, dan dicatat di activity log.

Setelah deploy, jalankan migrasi database dan pastikan Laravel Scheduler berjalan setiap menit di server (`php artisan schedule:run`). Hari Sabtu, Minggu, dan tanggal libur yang tercatat tidak dipotong.
