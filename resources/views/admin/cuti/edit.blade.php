<x-app-layout>
    <x-slot name="header">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3">
            <div>
                <h2 class="text-2xl font-bold text-gray-900 dark:text-white">Kelola Saldo Cuti</h2>
                <p class="mt-1 text-sm text-gray-600 dark:text-gray-400">{{ $user->name }} · {{ $user->id_karyawan }}</p>
            </div>
            <a href="{{ route('admin.absensi.recap') }}"
                class="inline-flex items-center gap-2 px-4 py-2 rounded-lg border border-gray-300 dark:border-gray-600 text-gray-700 dark:text-gray-300 hover:bg-gray-50 dark:hover:bg-gray-700">
                <i class="fas fa-arrow-left"></i> Kembali ke Rekap
            </a>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-2xl mx-auto space-y-6">
            @if (session('success'))
                <div role="status" class="rounded-lg border border-green-200 bg-green-50 p-4 text-green-800 dark:border-green-800 dark:bg-green-900/30 dark:text-green-200">
                    {{ session('success') }}
                </div>
            @endif

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <p class="text-sm font-medium text-gray-600 dark:text-gray-400">Saldo cuti saat ini</p>
                <p class="mt-2 text-4xl font-bold text-teal-700 dark:text-teal-300">
                    {{ $user->sisa_cuti ?? 0 }} <span class="text-lg font-medium">hari</span>
                </p>
                <p class="mt-3 text-sm text-gray-500 dark:text-gray-400">
                    Penyesuaian ini hanya mengubah saldo saat ini. Total cuti yang sudah diambil tidak berubah.
                </p>
            </section>

            <section class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm dark:border-gray-700 dark:bg-gray-800">
                <h3 class="text-lg font-semibold text-gray-900 dark:text-white">Buat penyesuaian</h3>

                <form method="POST" action="{{ route('admin.cuti.update', $user) }}" class="mt-5 space-y-5">
                    @csrf
                    @method('PATCH')

                    <div>
                        <label for="operation" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jenis penyesuaian</label>
                        <select id="operation" name="operation" required
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                            <option value="add" {{ old('operation') === 'add' ? 'selected' : '' }}>Tambah cuti</option>
                            <option value="subtract" {{ old('operation') === 'subtract' ? 'selected' : '' }}>Kurangi cuti</option>
                        </select>
                        @error('operation')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="days" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Jumlah hari</label>
                        <input id="days" name="days" type="number" min="1" max="365" step="1" required
                            value="{{ old('days') }}"
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">
                        @error('days')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="reason" class="mb-2 block text-sm font-medium text-gray-700 dark:text-gray-300">Alasan</label>
                        <textarea id="reason" name="reason" rows="3" maxlength="500" required
                            class="w-full rounded-lg border-gray-300 bg-white text-gray-900 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 dark:border-gray-600 dark:bg-gray-700 dark:text-white">{{ old('reason') }}</textarea>
                        @error('reason')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <button type="submit"
                        class="inline-flex items-center gap-2 rounded-lg bg-indigo-600 px-5 py-2.5 font-semibold text-white hover:bg-indigo-700 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2">
                        <i class="fas fa-save"></i> Simpan Penyesuaian
                    </button>
                </form>
            </section>
        </div>
    </div>
</x-app-layout>
