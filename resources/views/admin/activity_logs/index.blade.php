<x-app-layout>
<div class="py-12">
    <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
        <div class="bg-white dark:bg-gray-800 overflow-hidden shadow-sm sm:rounded-lg">
            <div class="p-6 text-gray-900 dark:text-gray-100">
                <h2 class="text-2xl font-bold mb-4">
                    <i class="fas fa-history text-indigo-500 mr-2"></i> Log Aktivitas Admin
                </h2>
                <p class="text-sm text-gray-500 dark:text-gray-400 mb-6">
                    Rekaman jejak digital persetujuan absensi, lembur, dll oleh semua role admin.
                </p>

                <div class="overflow-x-auto">
                    <table class="w-full text-sm text-left text-gray-500 dark:text-gray-400">
                        <thead class="text-xs text-gray-700 uppercase bg-gray-50 dark:bg-gray-700 dark:text-gray-400">
                            <tr>
                                <th scope="col" class="px-6 py-3">Waktu</th>
                                <th scope="col" class="px-6 py-3">Admin (Pelaku)</th>
                                <th scope="col" class="px-6 py-3">Role</th>
                                <th scope="col" class="px-6 py-3">Aksi</th>
                                <th scope="col" class="px-6 py-3">Target</th>
                                <th scope="col" class="px-6 py-3">Detail/Alasan</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse ($logs as $log)
                                <tr class="bg-white border-b dark:bg-gray-800 dark:border-gray-700 hover:bg-gray-50 dark:hover:bg-gray-600">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        {{ $log->created_at->format('d M Y, H:i') }}
                                    </td>
                                    <td class="px-6 py-4 font-medium text-gray-900 dark:text-white whitespace-nowrap">
                                        {{ $log->user->name ?? 'System/Unknown' }}
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-full 
                                            @if(($log->user->role ?? '') === 'super_admin' || ($log->user->role ?? '') === 'manager') bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300
                                            @elseif(($log->user->role ?? '') === 'supervisor') bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300
                                            @elseif(($log->user->role ?? '') === 'hrga') bg-emerald-100 text-emerald-800 dark:bg-emerald-900 dark:text-emerald-300
                                            @else bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300 @endif
                                        ">
                                            {{ strtoupper($log->user->role ?? 'UNKNOWN') }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        <span class="px-2 py-1 text-xs rounded-lg font-bold
                                            @if(str_contains(strtolower($log->action ?? ''), 'approve')) bg-green-100 text-green-700 border border-green-200 dark:bg-green-900/50 dark:border-green-800
                                            @elseif(str_contains(strtolower($log->action ?? ''), 'reject')) bg-red-100 text-red-700 border border-red-200 dark:bg-red-900/50 dark:border-red-800
                                            @else bg-gray-100 text-gray-700 border border-gray-200 dark:bg-gray-700 dark:text-gray-300 @endif
                                        ">
                                            {{ $log->action }}
                                        </span>
                                    </td>
                                    <td class="px-6 py-4">
                                        {{ $log->subject ?? '-' }}
                                    </td>
                                    <td class="px-6 py-4 max-w-xs truncate" title="{{ $log->details }}">
                                        {{ $log->details ?? '-' }}
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="6" class="px-6 py-4 text-center text-gray-500">
                                        Belum ada aktivitas yang direkam.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                <div class="mt-4">
                    {{ $logs->links() }}
                </div>
            </div>
        </div>
    </div>
</div>
</x-app-layout>
