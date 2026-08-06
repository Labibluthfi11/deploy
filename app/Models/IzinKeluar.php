<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IzinKeluar extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'tipe_izin',
        'tipe_durasi',
        'foto_surat',
        'alasan_keluar',
        'waktu_keluar',
        'waktu_kembali',
        'dokumen_kembali',
        'keterangan_kembali',
        'status_izin',
        'is_pelanggaran',
        'status_approval',
        'current_approval_level',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'waktu_keluar' => 'datetime',
        'waktu_kembali' => 'datetime',
        'is_pelanggaran' => 'boolean',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
