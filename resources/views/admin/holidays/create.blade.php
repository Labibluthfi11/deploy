{{-- resources/views/admin/holidays/create.blade.php --}}
<x-app-layout>
    <x-slot name="header">
        <div class="flex items-center justify-between">
            <div>
                <div class="flex items-center gap-2 mb-1">
                    <a href="{{ route('admin.holidays.index') }}" class="text-sm font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400 dark:hover:text-indigo-300">
                        <i class="fas fa-arrow-left mr-1"></i> Kembali ke Daftar
                    </a>
                </div>
                <h2 class="text-2xl font-bold text-gray-800 dark:text-white">
                    Tambah Hari Libur Manual
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mt-1">
                    Tambahkan Cuti Bersama atau kebijakan libur khusus perusahaan.
                </p>
            </div>
        </div>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto">
            <div class="bg-white/90 dark:bg-gray-800/90 backdrop-blur-xl rounded-2xl shadow-lg border border-gray-200/50 dark:border-gray-700/50 p-8">
                
                <form action="{{ route('admin.holidays.store') }}" method="POST" class="space-y-6">
                    @csrf
                    
                    <div>
                        <label for="holiday_date" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Tanggal Libur <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-calendar-day text-gray-400"></i>
                            </div>
                            <input type="date" name="holiday_date" id="holiday_date" value="{{ old('holiday_date') }}" required
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors @error('holiday_date') border-red-500 ring-red-500 @enderror">
                        </div>
                        @error('holiday_date')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div>
                        <label for="name" class="block text-sm font-semibold text-gray-700 dark:text-gray-300 mb-2">Keterangan / Nama Libur <span class="text-red-500">*</span></label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                <i class="fas fa-align-left text-gray-400"></i>
                            </div>
                            <input type="text" name="name" id="name" value="{{ old('name') }}" required placeholder="Contoh: Cuti Bersama Lebaran / Pengganti Hari Selasa"
                                class="block w-full pl-10 pr-4 py-3 bg-gray-50 border border-gray-300 rounded-xl focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 dark:bg-gray-700 dark:border-gray-600 dark:text-white transition-colors @error('name') border-red-500 ring-red-500 @enderror">
                        </div>
                        <p class="mt-2 text-xs text-gray-500 dark:text-gray-400">Libur yang ditambahkan manual akan berstatus "Kebijakan Perusahaan".</p>
                        @error('name')
                            <p class="mt-2 text-sm text-red-600 dark:text-red-400"><i class="fas fa-exclamation-circle mr-1"></i>{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="pt-6 border-t border-gray-200 dark:border-gray-700 flex justify-end gap-3">
                        <a href="{{ route('admin.holidays.index') }}" class="px-6 py-3 bg-white text-gray-700 border border-gray-300 rounded-xl hover:bg-gray-50 font-semibold transition-colors dark:bg-gray-800 dark:text-gray-300 dark:border-gray-600 dark:hover:bg-gray-700">
                            Batal
                        </a>
                        <button type="submit" class="px-6 py-3 bg-gradient-to-r from-indigo-600 to-purple-600 hover:from-indigo-700 hover:to-purple-700 text-white rounded-xl shadow-md hover:shadow-lg font-semibold transition-all">
                            Simpan Hari Libur
                        </button>
                    </div>
                </form>

            </div>
        </div>
    </div>
</x-app-layout>
