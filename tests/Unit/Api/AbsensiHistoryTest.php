<?php

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-10 15:00:00', 'Asia/Jakarta'));
    Storage::fake('public');

    Schema::dropIfExists('activity_logs');
    Schema::dropIfExists('holidays');
    Schema::dropIfExists('absensis');
    Schema::dropIfExists('users');

    Schema::create('users', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('email')->unique();
        $table->timestamp('email_verified_at')->nullable();
        $table->string('password');
        $table->string('remember_token')->nullable();
        $table->string('role')->default('user');
        $table->string('work_location')->nullable();
        $table->string('employment_type')->nullable();
        $table->unsignedInteger('sisa_cuti')->default(0);
        $table->unsignedInteger('total_cuti_diambil')->default(0);
        $table->timestamps();
    });

    Schema::create('absensis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->string('status');
        $table->string('tipe')->nullable();
        $table->string('submission_type')->nullable();
        $table->string('status_approval')->default('pending');
        $table->integer('current_approval_level')->default(1);
        $table->json('workflow_status')->nullable();
        $table->string('rejected_by')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->timestamp('rejected_at')->nullable();
        $table->timestamp('check_in_at')->nullable();
        $table->timestamp('check_out_at')->nullable();
        $table->date('end_date')->nullable();
        $table->unsignedInteger('total_days')->nullable();
        $table->unsignedInteger('hari_potong_cuti')->default(0);
        $table->unsignedInteger('hari_unpaid')->default(0);
        $table->string('jam_pulang_rencana')->nullable();
        $table->string('file_bukti')->nullable();
        $table->text('keterangan_izin_sakit')->nullable();
        $table->text('catatan_admin')->nullable();
        $table->text('lembur_keterangan')->nullable();
        $table->text('keterangan_goals')->nullable();
        $table->string('foto_masuk')->nullable();
        $table->string('foto_pulang')->nullable();
        $table->string('foto_pulang_2')->nullable();
        $table->string('foto_pulang_3')->nullable();
        $table->string('foto_pulang_4')->nullable();
        $table->string('foto_pulang_5')->nullable();
        $table->string('foto_pulang_6')->nullable();
        $table->string('lokasi_masuk')->nullable();
        $table->string('lokasi_pulang')->nullable();
        $table->timestamp('lembur_start')->nullable();
        $table->timestamp('lembur_end')->nullable();
        $table->integer('late_minutes')->nullable();
        $table->integer('rounded_late_minutes')->nullable();
        $table->integer('overtime_minutes')->nullable();
        $table->decimal('base_salary', 10, 2)->nullable();
        $table->decimal('late_penalty', 10, 2)->nullable();
        $table->decimal('final_salary', 10, 2)->nullable();
        $table->decimal('overtime_pay', 10, 2)->nullable();
        $table->boolean('is_weekend_overtime')->nullable();
        $table->boolean('is_mocked')->nullable();
        $table->boolean('is_kurang_8_jam')->nullable();
        $table->timestamps();
    });

    Schema::create('holidays', function (Blueprint $table) {
        $table->id();
        $table->date('holiday_date')->unique();
        $table->string('name')->nullable();
        $table->timestamps();
    });

    Schema::create('activity_logs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id')->nullable();
        $table->string('action');
        $table->string('subject')->nullable();
        $table->text('details')->nullable();
        $table->string('ip_address')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Carbon::setTestNow();
    Schema::dropIfExists('activity_logs');
    Schema::dropIfExists('holidays');
    Schema::dropIfExists('absensis');
    Schema::dropIfExists('users');
});

function createHistoryRecord(User $user, array $attributes = []): Absensi
{
    return Absensi::create(array_merge([
        'user_id' => $user->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => Carbon::parse('2026-11-10 08:00:00', 'Asia/Jakarta'),
    ], $attributes));
}

it('marks regular attendance as not an approval request and keeps its legacy fields', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $attendance = createHistoryRecord($user, [
        'workflow_status' => ['manager' => 'pending'],
        'current_approval_level' => 1,
    ]);
    createHistoryRecord($user, [
        'tipe' => 'izin',
        'workflow_status' => ['manager' => 'pending'],
    ]);

    $response = $this->getJson('/api/v1/absensi/me')->assertOk();

    foreach ($response->json('data') as $record) {
        expect($record['has_approval_request'])->toBeFalse()
            ->and($record['approval'])->toBeNull()
            ->and($record['workflow_status'])->toBeNull();
    }

    $record = collect($response->json('data'))->firstWhere('id', $attendance->id);
    expect($record['status'])->toBe('hadir')
        ->and($record['tipe'])->toBeNull()
        ->and($record['status_approval'])->toBe('approved')
        ->and($record['current_approval_level'])->toBe(1);

    expect($attendance->fresh()->workflow_status)->toBe(['manager' => 'pending']);
});

it('recognizes pending approved and rejected requests, including legacy leave records', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    createHistoryRecord($user, [
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'status_approval' => 'pending',
        'workflow_status' => ['manager' => 'pending'],
        'keterangan_izin_sakit' => 'Pengajuan cuti tahunan.',
    ]);
    createHistoryRecord($user, [
        'status' => 'hadir',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'approved',
        'workflow_status' => ['manager' => 'approved'],
        'catatan_admin' => 'izin_pulang_cepat',
    ]);
    $rejectedSickRequest = createHistoryRecord($user, [
        'status' => 'sakit',
        'tipe' => 'sakit',
        'status_approval' => 'rejected',
    ]);
    $rejectedSickRequest->forceFill([
        'catatan_admin' => 'Mohon lampirkan bukti yang sesuai.',
    ])->save();
    createHistoryRecord($user, [
        'status' => 'izin',
        'tipe' => 'izin',
        'status_approval' => 'pending',
    ]);

    $response = $this->getJson('/api/v1/absensi/me')->assertOk();
    $records = collect($response->json('data'))->keyBy(fn ($record) => $record['submission_type'] ?? $record['tipe']);

    expect($records['cuti_tahunan']['has_approval_request'])->toBeTrue()
        ->and($records['cuti_tahunan']['approval']['status'])->toBe('pending')
        ->and($records['cuti_tahunan']['approval']['request_type'])->toBe('cuti_tahunan')
        ->and($records['cuti_tahunan']['approval']['workflow'])->toBe(['manager' => 'pending'])
        ->and($records['cuti_tahunan']['approval']['reason'])->toBe('Pengajuan cuti tahunan.')
        ->and($records['izin_pulang_cepat']['approval']['status'])->toBe('approved')
        ->and($records['sakit']['approval']['status'])->toBe('rejected')
        ->and($records['sakit']['approval']['admin_note'])->toBe('Mohon lampirkan bukti yang sesuai.')
        ->and($records['izin']['has_approval_request'])->toBeTrue()
        ->and($records['izin']['approval']['request_type'])->toBe('izin');
});

it('keeps child requests separate from the parent and orders multiple children deterministically', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $parent = createHistoryRecord($user, [
        'workflow_status' => ['manager' => 'pending'],
        'current_approval_level' => 2,
    ]);
    $overtime = createHistoryRecord($user, [
        'parent_id' => $parent->id,
        'tipe' => 'lembur',
        'submission_type' => 'lembur',
        'status_approval' => 'pending',
        'workflow_status' => ['manager' => 'pending'],
    ]);
    $lateExplanation = createHistoryRecord($user, [
        'parent_id' => $parent->id,
        'tipe' => 'telat',
        'submission_type' => 'telat',
        'status_approval' => 'approved',
        'workflow_status' => ['manager' => 'approved'],
    ]);

    $firstResponse = $this->getJson('/api/v1/absensi/me')->assertOk();
    $secondResponse = $this->getJson('/api/v1/absensi/me')->assertOk();

    $first = $firstResponse->json('data.0');
    $second = $secondResponse->json('data.0');
    expect($first['has_approval_request'])->toBeFalse()
        ->and($first['approval'])->toBeNull()
        ->and($first['status'])->toBe('hadir')
        ->and($first['tipe'])->toBeNull()
        ->and($first['status_approval'])->toBe('approved')
        ->and($first['workflow_status'])->toBeNull()
        ->and(array_column($first['children'], 'id'))->toBe([$overtime->id, $lateExplanation->id])
        ->and(array_column($second['children'], 'id'))->toBe([$overtime->id, $lateExplanation->id])
        ->and($first['children'][0]['tipe'])->toBe('lembur')
        ->and($first['children'][0]['status_approval'])->toBe('pending')
        ->and($first['children'][0]['approval']['status'])->toBe('pending')
        ->and($first['children'][1]['tipe'])->toBe('telat')
        ->and($first['children'][1]['approval']['status'])->toBe('approved');

    expect($parent->fresh()->status)->toBe('hadir')
        ->and($parent->fresh()->tipe)->toBeNull()
        ->and($parent->fresh()->status_approval)->toBe('approved')
        ->and($parent->fresh()->workflow_status)->toBe(['manager' => 'pending']);
});

it('does not expose stale leave approval metadata as an active request after conversion to regular attendance', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $rejectedRequest = createHistoryRecord($user, [
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'workflow_status' => ['manager' => 'pending'],
        'status_approval' => 'rejected',
    ]);
    $rejectedRequest->forceFill(['catatan_admin' => 'cuti_tahunan'])->save();

    $this->postJson('/api/v1/absensi/masuk', [
        'foto' => UploadedFile::fake()->image('masuk.jpg'),
        'lat' => -6.2,
        'lng' => 106.8,
        'status' => 'hadir',
    ])->assertOk();

    $this->getJson('/api/v1/absensi/me')
        ->assertOk()
        ->assertJsonPath('data.0.has_approval_request', false)
        ->assertJsonPath('data.0.approval', null)
        ->assertJsonPath('data.0.workflow_status', null)
        ->assertJsonPath('data.0.submission_type', 'cuti_tahunan')
        ->assertJsonPath('data.0.status_approval', 'approved');

    expect($rejectedRequest->fresh()->workflow_status)->toBe(['manager' => 'pending'])
        ->and($rejectedRequest->fresh()->catatan_admin)->toBe('cuti_tahunan');
});
