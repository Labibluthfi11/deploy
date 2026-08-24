<x-app-layout>
    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="mb-6">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">Persetujuan Koreksi Absensi</h1>
                <p class="text-sm text-gray-600 dark:text-gray-400">Kelola pengajuan koreksi absensi karyawan</p>
            </div>

            @if (session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('success') }}
                </div>
            @endif

            @if (session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded relative" role="alert">
                    {{ session('error') }}
                </div>
            @endif

            <div class="bg-white dark:bg-gray-800 rounded-lg shadow overflow-hidden">
                <table class="min-w-full divide-y divide-gray-200 dark:divide-gray-700">
                    <thead class="bg-gray-50 dark:bg-gray-700">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Karyawan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Tanggal</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Alasan</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Bukti</th>
                            <th class="px-6 py-3 text-center text-xs font-medium text-gray-500 dark:text-gray-300 uppercase">Aksi</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-gray-200 dark:divide-gray-700">
                        @forelse($requests as $req)
                            <tr>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ $req->user->name }}</td>
                                <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-900 dark:text-white">{{ \Carbon\Carbon::parse($req->tanggal_absen)->format('d M Y') }}</td>
                                <td class="px-6 py-4 text-sm text-gray-900 dark:text-white">{{ $req->alasan }}</td>
                                <td class="px-6 py-4 whitespace-nowrap">
                                    @if(isset($req->data_koreksi['file_bukti']))
                                        <a href="{{ asset('storage/' . $req->data_koreksi['file_bukti']) }}" target="_blank" class="text-indigo-600 hover:text-indigo-900 text-sm">Lihat</a>
                                    @else
                                        -
                                    @endif
                                </td>
                                <td class="px-6 py-4 whitespace-nowrap text-center">
                                    <div class="flex justify-center gap-2">
                                        <form action="{{ route('admin.koreksi-absensi.approve', $req->id) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="bg-green-600 text-white px-3 py-1 rounded text-sm hover:bg-green-700">Approve</button>
                                        </form>
                                        <button type="button" 
                                            class="bg-red-600 text-white px-3 py-1 rounded text-sm hover:bg-red-700 reject-btn"
                                            data-id="{{ $req->id }}">
                                            Reject
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">Tidak ada pengajuan pending.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    @include('admin.absensi.approval.partials.reject-modal')

    <script nonce="{{ config('app.csp_nonce') }}">
        document.addEventListener('DOMContentLoaded', function() {
            document.querySelectorAll('.reject-btn').forEach(button => {
                button.addEventListener('click', function() {
                    const id = this.dataset.id;
                    const url = `{{ url('admin/koreksi-absensi') }}/${id}/reject`;
                    openRejectModal(id, url);
                });
            });
        });
    </script>
</x-app-layout>
