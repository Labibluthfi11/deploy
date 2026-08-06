{{-- SCHEDULED LEMBUR SECTION --}}
@if(isset($scheduledLembur) && $scheduledLembur->count() > 0)
<div class="mt-8 bg-white dark:bg-gray-800 rounded-2xl shadow-lg border border-gray-200 dark:border-gray-700 overflow-hidden">
    <div class="bg-gradient-to-r from-violet-600 to-purple-600 dark:from-violet-700 dark:to-purple-700 px-6 py-5 border-b border-gray-200 dark:border-gray-700">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="p-2 bg-white/20 backdrop-blur-sm rounded-lg">
                    <svg class="w-6 h-6 text-white dark:text-gray-900" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                </div>
                <div>
                    <h2 class="text-xl font-bold text-white dark:text-gray-900">Lembur Terjadwal</h2>
                    <p class="text-sm text-violet-100 mt-0.5">Pengajuan lembur terjadwal menunggu persetujuan</p>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <span class="px-3 py-1 bg-white/20 backdrop-blur-sm text-white text-sm font-semibold rounded-lg dark:text-gray-900">
                    {{ $scheduledLembur->count() }} Pending
                </span>
            </div>
        </div>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
            <thead class="bg-gray-50 dark:bg-gray-800/50">
                <tr>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Karyawan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Tanggal Lembur</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Keterangan</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Foto Bukti</th>
                    <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Status</th>
                    <th scope="col" class="px-6 py-3 text-center text-xs font-semibold text-gray-700 dark:text-gray-300 uppercase tracking-wider">Aksi</th>
                </tr>
            </thead>
            <tbody class="bg-white dark:bg-gray-800 divide-y divide-gray-200 dark:divide-gray-700">
                @foreach($scheduledLembur as $item)
                    <tr class="hover:bg-gray-50 dark:hover:bg-gray-700/50 transition-colors">
                        {{-- KARYAWAN --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="flex-shrink-0 h-10 w-10">
                                    <div class="h-10 w-10 rounded-full bg-gradient-to-br from-violet-500 to-purple-700 flex items-center justify-center text-white font-semibold text-sm shadow-lg dark:text-gray-900">
                                        {{ strtoupper(substr($item->user->name ?? 'U', 0, 2)) }}
                                    </div>
                                </div>
                                <div class="ml-4">
                                    <div class="text-sm font-semibold text-gray-900 dark:text-white">{{ $item->user->name ?? '-' }}</div>
                                    <div class="text-xs text-gray-500 dark:text-gray-400">{{ $item->user->id_karyawan ?? 'N/A' }}</div>
                                </div>
                            </div>
                        </td>

                        {{-- TANGGAL LEMBUR --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-xs font-semibold text-violet-600 dark:text-violet-400 uppercase">Lembur Terjadwal</div>
                            <div class="text-sm text-gray-900 dark:text-white font-medium mt-1">
                                {{ \Carbon\Carbon::parse($item->tanggal_lembur)->isoFormat('DD MMM YYYY') }}
                            </div>
                            <p class="text-xs text-gray-400 dark:text-gray-500 mt-1">Diajukan {{ $item->created_at ? \Carbon\Carbon::parse($item->created_at)->diffForHumans() : '-' }}</p>
                        </td>

                        {{-- KETERANGAN --}}
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700 dark:text-gray-300 max-w-xs">
                                <p class="truncate font-medium text-gray-900 dark:text-white" title="{{ $item->keterangan }}">
                                    {{ $item->keterangan }}
                                </p>
                            </div>
                        </td>

                        {{-- FOTO BUKTI --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if ($item->foto_bukti)
                                <a href="{{ asset('storage/' . $item->foto_bukti) }}" target="_blank"
                                   class="inline-flex items-center gap-2 px-3 py-1.5 bg-gray-100 dark:bg-gray-700 text-gray-700 dark:text-gray-300 rounded-lg text-xs font-medium hover:bg-gray-200 dark:hover:bg-gray-600 transition-all">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                    </svg>
                                    Lihat Bukti
                                </a>
                            @else
                                <span class="text-xs text-gray-400 dark:text-gray-500 italic">Tidak ada</span>
                            @endif
                        </td>

                        {{-- STATUS --}}
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold border bg-yellow-500/10 text-yellow-400 border-yellow-500/20">
                                <span class="mr-1">⏱️</span>
                                Menunggu
                            </span>
                        </td>

                        {{-- AKSI --}}
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <div class="flex items-center justify-center gap-2">
                                <form action="{{ route('admin.absensi.approval.scheduled-lembur.action', ['id' => $item->id, 'action' => 'approve']) }}"
                                    method="POST" class="inline">
                                    @csrf
                                    <button type="submit"
                                        onclick="return confirm('Apakah Anda yakin ingin MENYETUJUI lembur terjadwal ini?')"
                                        class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-green-600 hover:bg-green-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 focus:ring-green-500 transition-all shadow-sm hover:shadow dark:text-gray-900">
                                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                        </svg>
                                        Approve
                                    </button>
                                </form>

                                <button
                                    type="button"
                                    onclick="openRejectModal({{ $item->id }}, '{{ route('admin.absensi.approval.scheduled-lembur.action', ['id' => $item->id, 'action' => 'reject']) }}')"
                                    class="inline-flex items-center px-3 py-1.5 border border-transparent text-xs font-medium rounded-lg text-white bg-red-600 hover:bg-red-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-offset-white dark:focus:ring-offset-gray-800 focus:ring-red-500 transition-all shadow-sm hover:shadow dark:text-gray-900">
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
            Menampilkan <span class="font-medium text-gray-900 dark:text-white">{{ $scheduledLembur->count() }}</span> pengajuan lembur terjadwal
        </div>
    </div>
</div>
@endif
