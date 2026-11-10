<x-app-layout>
    <x-slot name="header">
        <div>
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Absensi Belum Absen Pulang</h2>
            <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                Checkout kosong pada absensi baru tidak dihitung sebagai satu shift penuh. Periksa kasus lama sebelum payroll.
            </p>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-7xl mx-auto">
            <div class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <div class="overflow-x-auto">
                    <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                        <thead class="bg-gray-50 dark:bg-gray-900">
                            <tr>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Karyawan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tanggal</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Jam Masuk</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Jam Pulang Diajukan</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Status</th>
                                <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-gray-500">Tindak Lanjut</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-100 dark:divide-gray-700">
                            @forelse ($absensis as $absensi)
                                @php
                                    $hasCorrection = in_array($absensi->submission_type, ['koreksi_lupa_pulang', 'koreksi_pulang_cepat'], true);
                                    $isLegacyAutoCheckout = $absensi->catatan_admin === 'AUTO_CHECKOUT_LUPA_ABSEN';
                                    $statusLabel = $isLegacyAutoCheckout
                                        ? 'Jam pulang otomatis — perlu verifikasi'
                                        : ($absensi->status_approval === 'pending'
                                            ? 'Menunggu approval'
                                            : ($absensi->status_approval === 'rejected' ? 'Ditolak' : 'Belum diajukan'));
                                    $approvalRoute = 'admin.absensi.approval.manager';
                                    if (strtolower($absensi->user->work_location ?? '') === 'produksi') {
                                        $approvalRoute = match ($absensi->current_approval_level) {
                                            1 => 'admin.absensi.approval.supervisor',
                                            2 => 'admin.absensi.approval.manager',
                                            default => 'admin.absensi.approval.hrga',
                                        };
                                    } elseif ($absensi->current_approval_level >= 2) {
                                        $approvalRoute = 'admin.absensi.approval.hrga';
                                    }
                                @endphp
                                <tr>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <div class="font-medium text-gray-900 dark:text-white">{{ $absensi->user->name ?? '-' }}</div>
                                        <div class="text-xs text-gray-500">{{ $absensi->user->id_karyawan ?? '-' }}</div>
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $absensi->check_in_at?->isoFormat('DD MMM YYYY') ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $absensi->check_in_at?->format('H:i') ?? '-' }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm text-gray-700 dark:text-gray-300">
                                        {{ $absensi->jam_pulang_rencana ? \Carbon\Carbon::parse($absensi->jam_pulang_rencana)->format('H:i') : ($isLegacyAutoCheckout && $absensi->check_out_at ? $absensi->check_out_at->format('H:i') . ' (otomatis)' : '-') }}
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4">
                                        <span class="rounded-full px-2.5 py-1 text-xs font-semibold
                                            {{ $absensi->status_approval === 'pending' ? 'bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-300' : ($absensi->status_approval === 'rejected' ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300' : 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300') }}">
                                            {{ $statusLabel }}
                                        </span>
                                        @if ($hasCorrection)
                                            <div class="mt-1 text-xs text-gray-500">
                                                {{ $absensi->submission_type === 'koreksi_pulang_cepat' ? 'Pulang lebih awal' : 'Lupa absen pulang' }}
                                            </div>
                                        @endif
                                    </td>
                                    <td class="whitespace-nowrap px-6 py-4 text-sm">
                                        <a href="{{ route('admin.absensi.user', $absensi->user_id) }}" class="font-semibold text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">
                                            Lihat detail
                                        </a>
                                        @if ($absensi->status_approval === 'pending')
                                            <a href="{{ route($approvalRoute) }}" class="ml-3 font-semibold text-emerald-600 hover:text-emerald-800 dark:text-emerald-400">
                                                Buka approval
                                            </a>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-12 text-center text-sm text-gray-500 dark:text-gray-400">
                                        Tidak ada absensi sebelumnya yang belum memiliki jam pulang.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                @if ($absensis->hasPages())
                    <div class="border-t border-gray-200 px-6 py-4 dark:border-gray-700">
                        {{ $absensis->links() }}
                    </div>
                @endif
            </div>
        </div>
    </div>
</x-app-layout>
