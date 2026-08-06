<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;
use Carbon\Carbon;

class AbsensiResource extends JsonResource
{
    public function toArray($request)
    {
        return [
            'id' => $this->id,
            'user_id' => $this->user_id,
            'nama_karyawan' => $this->user->name ?? 'User Tidak Ditemukan',
            'tanggal_absensi' => Carbon::parse($this->created_at)->translatedFormat('l, d F Y'),

            // Jam Kerja Utama
            'jam_masuk' => $this->check_in_at
                ? Carbon::parse($this->check_in_at)->format('H:i')
                : '-',

            'jam_keluar' => $this->check_out_at
                ? Carbon::parse($this->check_out_at)->format('H:i')
                : '--:--',

            // Status & Indikator
            'status' => $this->status,
            'is_late' => (bool) ($this->late_minutes > 0),
            'keterangan_telat' => $this->keterangan_izin_sakit ?? '-', // Map to correct field

            // Data Lembur
            'lembur_start' => $this->lembur_start
                ? Carbon::parse($this->lembur_start)->format('H:i')
                : null,
            'lembur_end' => $this->lembur_end
                ? Carbon::parse($this->lembur_end)->format('H:i')
                : null,
            'durasi_lembur' => ($this->lembur_start && $this->lembur_end)
                ? Carbon::parse($this->lembur_start)->diffInMinutes(Carbon::parse($this->lembur_end)) . ' Menit'
                : '0 Menit',

            // Bukti & Lokasi
            'foto_masuk_url' => $this->foto_masuk ? url('storage/' . $this->foto_masuk) : null,
            'foto_keluar_url' => $this->foto_pulang ? url('storage/' . $this->foto_pulang) : null,

            'lokasi_masuk' => [
                'lat' => explode(',', $this->lokasi_masuk)[0] ?? null,
                'long' => explode(',', $this->lokasi_masuk)[1] ?? null,
            ],
            'lokasi_pulang' => [
                'lat' => explode(',', $this->lokasi_pulang)[0] ?? null,
                'long' => explode(',', $this->lokasi_pulang)[1] ?? null,
            ],

            // Meta Data
            'terakhir_update' => $this->updated_at->diffForHumans(),
        ];
    }
}
