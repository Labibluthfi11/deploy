<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                Pantauan Izin Keluar Harian
            </h2>
            <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                Pantau karyawan yang izin keluar kantor hari ini
            </p>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto space-y-8">
            
            {{-- Summary Cards --}}
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-6 mb-6">
                <div class="relative overflow-hidden bg-gradient-to-br from-indigo-500 to-blue-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-indigo-100 text-xs font-medium uppercase">Izin Keluar Berjalan</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalIzinBerjalan }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-running text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-indigo-100 text-sm">Sedang di luar kantor</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-emerald-500 to-teal-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-emerald-100 text-xs font-medium uppercase">Izin Selesai</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalIzinSelesai }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-check-circle text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-emerald-100 text-sm">Sudah kembali / selesai</p>
                    </div>
                </div>

                <div class="relative overflow-hidden bg-gradient-to-br from-rose-500 to-red-600 rounded-2xl shadow-lg hover:shadow-xl transition-all p-6">
                    <div class="absolute top-0 right-0 w-24 h-24 bg-white/10 rounded-full -mr-12 -mt-12"></div>
                    <div class="relative">
                        <div class="flex items-center justify-between mb-3">
                            <div>
                                <p class="text-rose-100 text-xs font-medium uppercase">Melanggar Aturan</p>
                                <p class="text-white text-3xl font-bold mt-1">{{ $totalMelanggar }}</p>
                            </div>
                            <div class="p-3 bg-white/20 rounded-xl">
                                <i class="fas fa-exclamation-triangle text-white text-xl"></i>
                            </div>
                        </div>
                        <p class="text-rose-100 text-sm">Melebihi batas 2 jam</p>
                    </div>
                </div>
            </div>

            {{-- Table Data --}}
            <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-6">
                <div class="flex flex-col sm:flex-row items-center justify-between gap-4 mb-6">
                    <div class="flex items-center gap-3">
                        <div class="p-3 bg-gradient-to-br from-indigo-500 to-purple-600 rounded-xl">
                            <i class="fas fa-list text-white dark:text-gray-900"></i>
                        </div>
                        <div>
                            <h3 class="text-lg font-bold text-gray-800 dark:text-gray-100">Daftar Izin Keluar</h3>
                            <p class="text-sm text-gray-500 dark:text-gray-400">Data harian izin keluar karyawan</p>
                        </div>
                    </div>
                    
                    <form action="{{ route('admin.izin-keluar.index') }}" method="GET" class="flex gap-2">
                        <input type="date" name="date" value="{{ request('date', date('Y-m-d')) }}" class="px-4 py-2 border border-gray-300 dark:border-gray-600 rounded-lg dark:bg-gray-700 dark:text-white focus:ring-indigo-500">
                        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg shadow font-medium transition duration-200"><i class="fas fa-filter mr-1"></i> Filter</button>
                    </form>
                </div>

                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Karyawan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Tipe Izin</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Surat & Alasan</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Waktu Keluar</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Bukti Kembali</th>
                                <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 dark:text-gray-300 uppercase">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-800">
                            @forelse($izins as $izin)
                                <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                                    <td class="px-4 py-4">
                                        <div class="font-semibold text-gray-900 dark:text-gray-100">{{ $izin->user->name }}</div>
                                        <div class="text-xs text-gray-500">{{ ucfirst($izin->user->tipe_karyawan ?? 'Karyawan') }}</div>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        @if($izin->tipe_izin === 'mendesak')
                                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-rose-100 text-rose-800 dark:bg-rose-900/50 dark:text-rose-300">
                                                Mendesak (Max 2 Jam)
                                            </span>
                                        @else
                                            <span class="inline-flex flex-col gap-1 items-start">
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/50 dark:text-blue-300">
                                                    Keperluan Kantor
                                                </span>
                                                @if($izin->tipe_durasi)
                                                    <span class="text-xs text-gray-500">Durasi: {{ ucwords(str_replace('_', ' ', $izin->tipe_durasi)) }}</span>
                                                @endif
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 min-w-[200px]">
                                        <div class="text-sm text-gray-700 dark:text-gray-300 mb-2 truncate max-w-[200px]" title="{{ $izin->alasan_keluar }}">
                                            {{ $izin->alasan_keluar }}
                                        </div>
                                        <a href="{{ asset('storage/' . $izin->foto_surat) }}" target="_blank" class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-indigo-600 hover:text-indigo-800 hover:bg-gray-200 rounded transition-colors">
                                            <i class="fas fa-image"></i> Lihat Surat
                                        </a>
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="text-sm font-medium text-gray-900 dark:text-gray-100">
                                            {{ \Carbon\Carbon::parse($izin->waktu_keluar)->format('H:i') }}
                                        </div>
                                        <div class="text-xs text-gray-500">
                                            {{ \Carbon\Carbon::parse($izin->waktu_keluar)->format('d M') }}
                                        </div>
                                    </td>
                                    <td class="px-4 py-4 min-w-[150px]">
                                        @if($izin->waktu_kembali)
                                            <div class="text-sm font-medium text-gray-900 dark:text-gray-100 mb-1">
                                                Jam: {{ \Carbon\Carbon::parse($izin->waktu_kembali)->format('H:i') }}
                                            </div>
                                            @if($izin->dokumen_kembali)
                                                <a href="{{ asset('storage/' . $izin->dokumen_kembali) }}" target="_blank" class="inline-flex items-center gap-1 text-xs px-2 py-1 bg-gray-100 dark:bg-gray-700 text-emerald-600 hover:text-emerald-800 hover:bg-gray-200 rounded transition-colors mb-1">
                                                    <i class="fas fa-file-alt"></i> Foto Bukti
                                                </a>
                                            @endif
                                            @if($izin->keterangan_kembali)
                                                <div class="text-xs text-gray-500 italic max-w-[150px] truncate" title="{{ $izin->keterangan_kembali }}">
                                                    "{{ $izin->keterangan_kembali }}"
                                                </div>
                                            @endif
                                        @else
                                            <span class="text-sm text-gray-400 italic">Belum kembali...</span>
                                        @endif
                                    </td>
                                    <td class="px-4 py-4 whitespace-nowrap">
                                        <div class="flex flex-col gap-1 items-start">
                                            @if($izin->status_izin === 'selesai')
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-emerald-100 text-emerald-800 dark:bg-emerald-900/50 dark:text-emerald-300">
                                                    <i class="fas fa-check-circle mr-1 text-xs"></i> Selesai
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/50 dark:text-amber-300 animate-pulse">
                                                    <i class="fas fa-spinner fa-spin mr-1 text-xs"></i> Berjalan
                                                </span>
                                            @endif

                                            @if($izin->is_pelanggaran)
                                                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-800 dark:bg-red-900/50 dark:text-red-300 border border-red-200 mt-1">
                                                    <i class="fas fa-ban mr-1 text-xs"></i> Melanggar (Lewat 2 Jam)
                                                </span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="text-center py-12">
                                        <div class="flex flex-col items-center">
                                            <i class="fas fa-door-open text-4xl text-gray-300 dark:text-gray-600 mb-3 block"></i>
                                            <p class="text-gray-500 dark:text-gray-400 font-medium">Tidak ada pengajuan izin keluar pada tanggal ini.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                
                @if($izins->hasPages())
                    <div class="mt-4 px-4">
                        {{ $izins->links() }}
                    </div>
                @endif
            </div>

        </div>
    </div>
</x-app-layout>
