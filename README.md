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

## Pemeriksaan Wajah Absensi

Endpoint absen masuk dengan status `hadir` dan foto utama absen pulang memeriksa bahwa foto berisi wajah sebelum foto disimpan. Pemeriksaan ini hanya mendeteksi keberadaan wajah, bukan mencocokkan identitas. Foto bukti tambahan tidak diperiksa.

Backend menjalankan OpenCV secara lokal melalui Python; foto tidak dikirim ke layanan pihak ketiga. Pasang dependensi pada server saat deploy dengan `python -m pip install -r requirements.txt` (atau gunakan executable Python yang sesuai), lalu set `FACE_DETECTOR_PYTHON` jika perintah Python server bukan `python3` (`python` pada Windows). Jika wajah tidak terdeteksi, API mengembalikan HTTP 422 pada field `foto`. Jika OpenCV/Python tidak tersedia, API mengembalikan HTTP 503 dan absensi tidak disimpan.
