{{-- IZIN KELUAR SECTION --}}
@if(isset($izinKeluar) && $izinKeluar->count() > 0)
<div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="bg-gradient-to-r from-orange-500 to-amber-600 dark:from-orange-700 dark:to-amber-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white dark:text-gray-900">Izin Keluar</h2>
                    <p class="text-sm text-orange-100 mt-0.5">Pengajuan izin keluar menunggu persetujuan</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-semibold rounded-lg dark:text-gray-900">
                    {{ $izinKeluar->count() }} Pending
                </span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Karyawan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tipe & Waktu</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Alasan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Bukti</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($izinKeluar as $item)
                    @php
                        $waktuKeluar = \Carbon\Carbon::parse($item->waktu_keluar);
                        $waktuKembali = $item->waktu_kembali ? \Carbon\Carbon::parse($item->waktu_kembali) : null;
                        $durasiMenit = $waktuKembali ? $waktuKeluar->diffInMinutes($waktuKembali) : null;
                        $durasiJam = $durasiMenit ? floor($durasiMenit / 60) : 0;
                        $durasiSisaMenit = $durasiMenit ? $durasiMenit % 60 : 0;
                    @endphp
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        {{-- KARYAWAN --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-orange-500 to-amber-700 flex items-center justify-center text-white font-semibold text-sm shadow-lg dark:text-gray-900">
                                        {{ strtoupper(substr($item->user->name ?? 'U', 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->user->id_karyawan ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- TIPE & WAKTU --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs font-semibold uppercase {{ $item->tipe_izin === 'mendesak' ? 'text-red-600 dark:text-red-400' : 'text-blue-600 dark:text-blue-400' }}">
                                {{ $item->tipe_izin === 'mendesak' ? 'Mendesak (Maks 2 Jam)' : 'Kepentingan Kantor (Bebas)' }}
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mt-1">
                                Keluar: {{ $waktuKeluar->format('d M Y, H:i') }}
                            </div>
                            <div class="text-sm text-gray-900 dark:text-white">
                                Kembali: {{ $waktuKembali ? $waktuKembali->format('H:i') : '-' }}
                            </div>
                            @if($durasiMenit !== null)
                                <p class="text-xs {{ $item->is_pelanggaran ? 'text-red-500 font-semibold' : 'text-gray-400 dark:text-gray-500' }} mt-1">
                                    Durasi: {{ $durasiJam }} jam {{ $durasiSisaMenit }} menit
                                </p>
                            @endif
                            @if($item->is_pelanggaran)
                                <span class="inline-flex items-center gap-1 mt-1 px-2 py-0.5 rounded-full text-xs font-semibold bg-red-500/10 text-red-500 border border-red-500/20">
                                    ⚠️ Melebihi batas 2 jam
                                </span>
                            @endif
                        </td>

                        {{-- ALASAN --}}
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                                <p class="font-medium text-gray-900 dark:text-white mb-1" title="{{ $item->alasan_keluar }}">
                                    {{ Str::limit($item->alasan_keluar, 60) }}
                                </p>
                                @if($item->keterangan_kembali)
                                    <p class="text-xs text-gray-500 dark:text-gray-400 italic" title="{{ $item->keterangan_kembali }}">
                                        Kembali: {{ Str::limit($item->keterangan_kembali, 50) }}
                                    </p>
                                @endif
                            </div>
                        </td>

                        {{-- BUKTI --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col gap-1.5">
                                @if($item->foto_surat)
                                    <a href="{{ asset('storage/' . $item->foto_surat) }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Surat Keluar
                                    </a>
                                @endif
                                @if($item->dokumen_kembali)
                                    <a href="{{ asset('storage/' . $item->dokumen_kembali) }}" target="_blank"
                                       class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                        </svg>
                                        Dokumen Kembali
                                    </a>
                                @endif
                            </div>
                        </td>

                        {{-- STATUS --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-yellow-500/10 text-yellow-400 border-yellow-500/20">
                                <span class="mr-1">⏱️</span>
                                Menunggu (Level {{ $item->current_approval_level }})
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <form action="{{ route('admin.absensi.approval.izin-keluar.action', ['id' => $item->id, 'action' => 'approve']) }}"
                                    method="POST" class="inline izin-keluar-approve-form">
                                    @csrf
                                    <button type="submit"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 focus:ring-green-500 transition-all shadow-sm hover:shadow dark:text-gray-900">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    class="izin-keluar-reject-btn inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 focus:ring-red-500 transition-all shadow-sm hover:shadow dark:text-gray-900"
                                    data-id="{{ $item->id }}"
                                    data-reject-url="{{ route('admin.absensi.approval.izin-keluar.action', ['id' => $item->id, 'action' => 'reject']) }}">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                    </svg>
                                    Reject
                                </button>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    {{-- Footer --}}
    <div class="px-6 py-4 border-t border-gray-200 dark:border-gray-700 bg-gray-50 dark:bg-gray-800/50">
        <div class="text-sm text-gray-600 dark:text-gray-400">
            Menampilkan <span class="font-medium text-gray-900 dark:text-white">{{ $izinKeluar->count() }}</span> pengajuan izin keluar
        </div>
    </div>
</div>

<script nonce="{{ config('app.csp_nonce') }}">
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.izin-keluar-reject-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                openRejectModal(btn.dataset.id, btn.dataset.rejectUrl);
            });
        });
    });
</script>
@endif
