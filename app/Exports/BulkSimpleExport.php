<?php

namespace App\Exports;

use App\Models\User;
use App\Models\Absensi;
use Illuminate\Contracts\View\View;
use Maatwebsite\Excel\Concerns\FromView;
use Maatwebsite\Excel\Concerns\ShouldAutoSize;
use Maatwebsite\Excel\Concerns\WithTitle;
use Carbon\Carbon;

class BulkSimpleExport implements FromView, ShouldAutoSize, WithTitle
{
    protected $userIds;
    protected $startDate;
    protected $endDate;

    public function __construct($userIds, $startDate, $endDate)
    {
        $this->userIds = $userIds;
        $this->startDate = $startDate;
        $this->endDate = $endDate;
    }

    public function view(): View
    {
        $users = User::whereIn('id', $this->userIds)->orderBy('name')->get();

        // 🔥 DEBUG: LOG USER IDS
        \Log::info('🔍 EXPORT USERS:', [
            'user_ids' => $this->userIds,
            'user_names' => $users->pluck('name', 'id')->toArray(),
        ]);

        // 🔥 AMBIL SEMUA DATA
        $absensiData = Absensi::whereIn('user_id', $this->userIds)
            ->whereBetween('check_in_at', [$this->startDate, $this->endDate])
            ->where('status_approval', 'approved')
            ->orderBy('check_in_at', 'asc')
            ->get();

        // 🔥 DEBUG: LOG QUERY RESULT
        \Log::info('🔍 EXPORT ABSENSI DATA:', [
            'date_range' => [
                'start' => $this->startDate,
                'end' => $this->endDate,
            ],
            'total_data' => $absensiData->count(),
            'sample_data' => $absensiData->take(5)->map(function($a) {
                return [
                    'user_id' => $a->user_id,
                    'user_name' => $a->user->name ?? 'N/A',
                    'check_in' => $a->check_in_at->format('Y-m-d H:i:s'),
                    'status' => $a->status,
                    'status_approval' => $a->status_approval,
                ];
            })->toArray(),
        ]);

        // 🔥 PECAH TANGGAL
        $sections = $this->splitDatesByMonth($this->startDate, $this->endDate);

        // 🔥 DEBUG: LOG SECTIONS
        \Log::info('📅 SECTIONS:', [
            'total_sections' => count($sections),
            'first_section_dates' => isset($sections[0]) ? count($sections[0]['dates']) : 0,
            'first_date' => isset($sections[0]['dates'][0]) ? $sections[0]['dates'][0]->format('Y-m-d') : null,
        ]);

        // Deteksi kategori
        $categories = [];
        foreach ($users as $user) {
            $kategori = $this->detectKategori($user);
            $categories[] = $kategori;
        }

        $uniqueCategories = array_unique($categories);

        if (count($uniqueCategories) === 1) {
            $singleCategory = $uniqueCategories[0];
            $categoryLabel = match($singleCategory) {
                'organik' => 'Karyawan Organik',
                'freelance' => 'Karyawan Freelance',
                'borongan' => 'Karyawan Borongan',
                'magang' => 'Karyawan Magang',
                default => 'Semua Karyawan'
            };
        } else {
            $categoryLabel = 'Semua Karyawan';
        }

        $periodeStr = Carbon::parse($this->startDate)->translatedFormat('d M Y') . ' s/d ' . Carbon::parse($this->endDate)->translatedFormat('d M Y');

        return view('exports.bulk_simple', [
            'users' => $users,
            'absensiData' => $absensiData,
            'sections' => $sections,
            'periodeStr' => $periodeStr,
            'categoryLabel' => $categoryLabel,
        ]);
    }

    private function splitDatesByMonth($start, $end)
    {
        $sections = [];
        $current = Carbon::parse($start);
        $endDate = Carbon::parse($end);

        $currentMonth = $current->month;
        $weekDates = [];
        $weekNumber = 1;

        while ($current <= $endDate) {
            if ($current->month != $currentMonth) {
                if (!empty($weekDates)) {
                    $sections[] = [
                        'month' => Carbon::create(null, $currentMonth)->translatedFormat('F Y'),
                        'week' => $weekNumber,
                        'dates' => $weekDates,
                    ];
                }

                $currentMonth = $current->month;
                $weekDates = [];
                $weekNumber = 1;
            }

            $weekDates[] = $current->copy();

            if (count($weekDates) >= 15) {
                $sections[] = [
                    'month' => $current->translatedFormat('F Y'),
                    'week' => $weekNumber,
                    'dates' => $weekDates,
                ];

                $weekDates = [];
                $weekNumber++;
            }

            $current->addDay();
        }

        if (!empty($weekDates)) {
            $sections[] = [
                'month' => Carbon::create(null, $currentMonth)->translatedFormat('F Y'),
                'week' => $weekNumber,
                'dates' => $weekDates,
            ];
        }

        return $sections;
    }

    private function detectKategori(User $user): string
    {
        $idKaryawan = $user->id_karyawan ?? '';

        if (strpos($idKaryawan, 'CS-AMB') === 0) {
            return 'borongan';
        }

        if (strpos($idKaryawan, 'MG-AMB') === 0) {
            return 'magang';
        }

        if (strpos($idKaryawan, 'AMB') === 0) {
            return 'freelance';
        }

        return $user->employment_type ?? 'organik';
    }

    public function title(): string
    {
        return 'Absensi Simple';
    }
}
