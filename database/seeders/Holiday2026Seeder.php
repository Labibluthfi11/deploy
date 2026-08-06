<?php

namespace Database\Seeders;

use App\Models\Holiday;
use Illuminate\Database\Seeder;

class Holiday2026Seeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $holidays = [
            // Libur Nasional 2026
            ['date' => '2026-01-01', 'name' => 'Tahun Baru 2026 Masehi'],
            ['date' => '2026-01-16', 'name' => 'Isra Mikraj Nabi Muhammad saw.'],
            ['date' => '2026-02-17', 'name' => 'Tahun Baru Imlek 2577 Kongzili'],
            ['date' => '2026-03-19', 'name' => 'Hari Suci Nyepi (Tahun Baru Saka 1948)'],
            ['date' => '2026-03-21', 'name' => 'Idulfitri 1447 H'],
            ['date' => '2026-03-22', 'name' => 'Idulfitri 1447 H'],
            ['date' => '2026-04-03', 'name' => 'Wafat Yesus Kristus'],
            ['date' => '2026-04-05', 'name' => 'Kebangkitan Yesus Kristus (Paskah)'],
            ['date' => '2026-05-01', 'name' => 'Hari Buruh Internasional'],
            ['date' => '2026-05-14', 'name' => 'Kenaikan Yesus Kristus'],
            ['date' => '2026-05-27', 'name' => 'Iduladha 1447 H'],
            ['date' => '2026-05-31', 'name' => 'Hari Raya Waisak 2570 BE'],
            ['date' => '2026-06-01', 'name' => 'Hari Lahir Pancasila'],
            ['date' => '2026-06-16', 'name' => '1 Muharam Tahun Baru Islam 1448 H'],
            ['date' => '2026-08-17', 'name' => 'Proklamasi Kemerdekaan'],
            ['date' => '2026-08-25', 'name' => 'Maulid Nabi Muhammad saw.'],
            ['date' => '2026-12-25', 'name' => 'Kelahiran Yesus Kristus (Natal)'],

            // Cuti Bersama 2026
            ['date' => '2026-02-16', 'name' => 'Cuti Bersama Tahun Baru Imlek'],
            ['date' => '2026-03-18', 'name' => 'Cuti Bersama Hari Suci Nyepi'],
            ['date' => '2026-03-20', 'name' => 'Cuti Bersama Idulfitri 1447 H'],
            ['date' => '2026-03-23', 'name' => 'Cuti Bersama Idulfitri 1447 H'],
            ['date' => '2026-03-24', 'name' => 'Cuti Bersama Idulfitri 1447 H'],
            ['date' => '2026-05-15', 'name' => 'Cuti Bersama Kenaikan Yesus Kristus'],
            ['date' => '2026-05-28', 'name' => 'Cuti Bersama Iduladha 1447 H'],
            ['date' => '2026-12-24', 'name' => 'Cuti Bersama Kelahiran Yesus Kristus'],
        ];

        foreach ($holidays as $holiday) {
            Holiday::updateOrCreate(
                ['holiday_date' => $holiday['date']],
                [
                    'name' => $holiday['name'],
                    'is_national' => true
                ]
            );
        }
    }
}
