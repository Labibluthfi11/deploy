<?php

use App\Models\User;
use Tests\TestCase;

uses(TestCase::class);

it('honors an explicit organic employment type over an AMB employee ID', function () {
    $user = new User([
        'id_karyawan' => 'AMB00013',
        'employment_type' => 'organik',
    ]);

    expect($user->kategori_absensi)->toBe('organik');
});

it('still infers freelance from an AMB employee ID when employment type is missing', function () {
    $user = new User(['id_karyawan' => 'AMB00013']);

    expect($user->kategori_absensi)->toBe('freelance');
});
