<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col md:flex-row justify-between items-start md:items-center gap-4">
            <div>
                <h2 class="font-bold text-3xl leading-tight text-gray-900 dark:text-white">Terbitkan Slip Gaji</h2>
                <p class="text-sm text-gray-600 dark:text-gray-400 mt-1">Untuk karyawan Freelance dan Borongan</p>
            </div>
            <a href="{{ route('admin.absensi.recap') }}"
                class="inline-flex items-center px-4 py-2.5 bg-white dark:bg-gray-800 border border-gray-300 dark:border-gray-600 rounded-lg text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700 transition">
                Kembali ke rekap
            </a>
        </div>
    </x-slot>

    <div class="py-8 bg-gray-50 dark:bg-gray-900 min-h-screen">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            @if (session('success'))
                <div class="rounded-lg bg-green-100 px-4 py-3 text-green-800">{{ session('success') }}</div>
            @endif
            @if (session('error'))
                <div class="rounded-lg bg-red-100 px-4 py-3 text-red-800">{{ session('error') }}</div>
            @endif
            @if ($errors->any())
                <div class="rounded-lg bg-red-100 px-4 py-3 text-red-800">
                    <ul class="list-disc pl-5">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            @php
                $selectedMode = old('recipient_mode', $formData['recipient_mode'] ?? 'all');
                $selectedIds = array_map('strval', old('user_ids', $formData['user_ids'] ?? []));
                $startValue = old('start_date', $formData['start_date'] ?? now()->subDays(13)->format('Y-m-d'));
                $endValue = old('end_date', $formData['end_date'] ?? now()->format('Y-m-d'));
            @endphp

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Periode dan penerima</h3>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                    Periode menggunakan tanggal custom. Hanya absensi yang sudah approved yang dihitung.
                </p>

                <form method="POST" action="{{ route('admin.salary-slips.preview') }}" class="mt-6 space-y-6">
                    @csrf
                    <div class="grid grid-cols-1 gap-4 sm:grid-cols-2">
                        <div>
                            <label for="start_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Dari tanggal</label>
                            <input id="start_date" name="start_date" type="date" required value="{{ $startValue }}"
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                        <div>
                            <label for="end_date" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Sampai tanggal</label>
                            <input id="end_date" name="end_date" type="date" required value="{{ $endValue }}"
                                class="w-full rounded-lg border-gray-300 bg-gray-50 text-gray-900 focus:border-blue-500 focus:ring-blue-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        </div>
                    </div>

                    <fieldset>
                        <legend class="mb-3 text-sm font-medium text-gray-700 dark:text-gray-300">Pilih penerima</legend>
                        <div class="flex flex-wrap gap-6">
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="recipient_mode" value="all" @checked($selectedMode === 'all')
                                    class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                Semua Freelance dan Borongan
                            </label>
                            <label class="inline-flex items-center gap-2 text-sm text-gray-700 dark:text-gray-300">
                                <input type="radio" name="recipient_mode" value="selected" @checked($selectedMode === 'selected')
                                    class="border-gray-300 text-blue-600 focus:ring-blue-500">
                                Pilih karyawan tertentu
                            </label>
                        </div>
                    </fieldset>

                    <div class="grid grid-cols-1 gap-3 sm:grid-cols-2 lg:grid-cols-3">
                        @foreach ($users as $user)
                            <label class="flex items-center gap-3 rounded-lg border border-gray-200 p-3 dark:border-gray-700">
                                <input type="checkbox" name="user_ids[]" value="{{ $user->id }}"
                                    @checked(in_array((string) $user->id, $selectedIds, true))
                                    class="rounded border-gray-300 text-blue-600 focus:ring-blue-500">
                                <span>
                                    <span class="block text-sm font-medium text-gray-900 dark:text-white">{{ $user->name }}</span>
                                    <span class="block text-xs capitalize text-gray-500 dark:text-gray-400">
                                        {{ $user->kategori_absensi }}{{ $user->id_karyawan ? ' · ' . $user->id_karyawan : '' }}
                                    </span>
                                </span>
                            </label>
                        @endforeach
                    </div>

                    @if ($users->isEmpty())
                        <p class="text-sm text-amber-700">Belum ada karyawan Freelance atau Borongan yang dapat dipilih.</p>
                    @endif

                    <button type="submit"
                        class="rounded-lg bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">
                        Lihat preview
                    </button>
                </form>
            </section>

            @isset($previewRows)
                <section class="overflow-hidden rounded-2xl border border-gray-200 bg-white shadow-sm dark:border-gray-700 dark:bg-gray-800">
                    <div class="border-b border-gray-200 p-6 dark:border-gray-700">
                        <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Preview slip</h3>
                        <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">
                            {{ $periodStart->format('d M Y') }} - {{ $periodEnd->format('d M Y') }}
                        </p>
                    </div>

                    <div class="overflow-x-auto">
                        <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                            <thead class="bg-gray-50 dark:bg-gray-900">
                                <tr>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Karyawan</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Hadir</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Upah pokok</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Lembur</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Potongan telat</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Penyesuaian</th>
                                    <th class="px-4 py-3 text-right text-xs font-semibold uppercase">Gaji bersih</th>
                                    <th class="px-4 py-3 text-left text-xs font-semibold uppercase">Status</th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                                @foreach ($previewRows as $row)
                                    <tr>
                                        <td class="px-4 py-3 text-sm text-gray-900 dark:text-white">
                                            {{ $row['user']->name }}
                                            <span class="block text-xs capitalize text-gray-500">{{ $row['user']->kategori_absensi }}</span>
                                        </td>
                                        @if ($row['payroll'])
                                            <td class="px-4 py-3 text-right text-sm">{{ $row['payroll']['attendance_days'] }}</td>
                                            <td class="px-4 py-3 text-right text-sm">Rp {{ number_format($row['payroll']['base_salary'], 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right text-sm">Rp {{ number_format($row['payroll']['overtime_pay'], 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right text-sm">Rp {{ number_format($row['payroll']['late_penalty'], 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right text-sm">Rp {{ number_format($row['payroll']['adjustment_salary'], 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-right text-sm font-semibold">Rp {{ number_format($row['payroll']['net_salary'], 0, ',', '.') }}</td>
                                        @else
                                            <td colspan="6" class="px-4 py-3 text-center text-sm text-gray-500">Tidak ada absensi approved pada periode ini</td>
                                        @endif
                                        <td class="px-4 py-3 text-sm">
                                            @if ($row['existing_slip'])
                                                <span class="text-amber-700">Sudah ada slip untuk periode yang bertabrakan</span>
                                            @elseif ($row['payroll'])
                                                <span class="text-green-700">Siap diterbitkan</span>
                                            @else
                                                <span class="text-gray-500">Dilewati</span>
                                            @endif
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    @if ($publishableCount > 0)
                        <form method="POST" action="{{ route('admin.salary-slips.issue') }}" class="flex flex-wrap items-center justify-between gap-4 p-6">
                            @csrf
                            <input type="hidden" name="start_date" value="{{ $formData['start_date'] }}">
                            <input type="hidden" name="end_date" value="{{ $formData['end_date'] }}">
                            <input type="hidden" name="recipient_mode" value="{{ $formData['recipient_mode'] }}">
                            @if ($formData['recipient_mode'] === 'selected')
                                @foreach ($formData['user_ids'] as $userId)
                                    <input type="hidden" name="user_ids[]" value="{{ $userId }}">
                                @endforeach
                            @endif
                            <p class="text-sm text-gray-600 dark:text-gray-400">
                                {{ $publishableCount }} slip siap diterbitkan. Angka akan diambil dari data payroll approved saat penerbitan.
                            </p>
                            <button type="submit"
                                class="rounded-lg bg-green-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-green-700">
                                Terbitkan slip
                            </button>
                        </form>
                    @endif
                </section>
            @endisset
        </div>
    </div>
</x-app-layout>
