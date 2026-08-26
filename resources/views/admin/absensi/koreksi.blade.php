<x-app-layout>
    <x-slot name="header">
        <h2 class="text-2xl font-bold text-gray-800 dark:text-white">Input Absensi Manual</h2>
    </x-slot>

    <div class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-3xl mx-auto bg-white dark:bg-gray-800 rounded-2xl shadow-lg p-6 border border-gray-200 dark:border-gray-700">
            
            <form action="{{ route('admin.absensi.store-manual') }}" method="POST">
                @csrf
                <div class="space-y-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Pilih Karyawan</label>
                        <select name="user_id" class="w-full mt-1 p-2.5 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                            @foreach($users as $u)
                                <option value="{{ $u->id }}">{{ $u->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Tanggal</label>
                        <input type="date" name="tanggal" required class="w-full mt-1 p-2.5 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                    </div>
                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Masuk</label>
                            <input type="time" name="jam_masuk" required class="w-full mt-1 p-2.5 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Jam Pulang</label>
                            <input type="time" name="jam_pulang" required class="w-full mt-1 p-2.5 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-gray-300">Keterangan (Berita Acara)</label>
                        <textarea name="keterangan" required class="w-full mt-1 p-2.5 border rounded-lg dark:bg-gray-700 dark:text-white dark:border-gray-600" rows="4"></textarea>
                    </div>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" class="px-6 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white font-semibold rounded-lg shadow transition-all">Simpan Absensi</button>
                </div>
            </form>
        </div>
    </div>
</x-app-layout>