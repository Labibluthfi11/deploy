<?php

use App\Models\Absensi;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;

uses(Tests\TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-04 15:00:00', 'Asia/Jakarta'));
    Storage::fake('public');

    Schema::dropIfExists('notifications');
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
        $table->timestamp('check_in_at')->nullable();
        $table->timestamp('check_out_at')->nullable();
        $table->string('file_bukti')->nullable();
        $table->text('keterangan_izin_sakit')->nullable();
        $table->text('catatan_admin')->nullable();
        $table->string('foto_pulang')->nullable();
        $table->string('lokasi_pulang')->nullable();
        $table->integer('late_minutes')->nullable();
        $table->integer('rounded_late_minutes')->nullable();
        $table->decimal('base_salary', 10, 2)->nullable();
        $table->decimal('late_penalty', 10, 2)->nullable();
        $table->decimal('final_salary', 10, 2)->nullable();
        $table->decimal('overtime_pay', 10, 2)->nullable();
        $table->boolean('is_kurang_8_jam')->nullable();
        $table->timestamps();
    });

    Schema::create('notifications', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('title');
        $table->text('message');
        $table->string('type')->nullable();
        $table->string('target_page')->nullable();
        $table->unsignedBigInteger('target_id')->nullable();
        $table->boolean('is_read')->default(false);
        $table->timestamps();
    });

    Schema::create('holidays', function (Blueprint $table) {
        $table->id();
        $table->date('holiday_date')->unique();
        $table->string('name')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Carbon::setTestNow();
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('holidays');
    Schema::dropIfExists('absensis');
    Schema::dropIfExists('users');
});

function createAttendanceWithoutCheckout(User $user, Carbon $checkIn): Absensi
{
    return Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'tipe' => null,
        'status_approval' => 'approved',
        'check_in_at' => $checkIn,
    ]);
}

function forgottenCheckoutPayload(Absensi $attendance): array
{
    return [
        'absensi_id' => $attendance->id,
        'jam_pulang' => '17:00',
        'keterangan' => 'Lupa melakukan absen pulang.',
        'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ];
}

it('creates a pending checkout correction without changing the attendance', function () {
    $user = User::factory()->create([
        'work_location' => 'office',
        'employment_type' => 'organik',
    ]);
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout(
        $user,
        Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta')
    );

    $response = $this->postJson('/api/v1/absensi/pengajuan-lupa-pulang', forgottenCheckoutPayload($attendance));

    $response->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tipe', 'koreksi lupa absen pulang')
        ->assertJsonPath('data.status_approval', 'pending');

    $correctionId = $response->json('data.id');
    $this->assertDatabaseHas('absensis', [
        'id' => $correctionId,
        'parent_id' => $attendance->id,
        'submission_type' => 'koreksi_lupa_pulang',
        'status_approval' => 'pending',
    ]);
    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'check_out_at' => null,
    ]);
    $this->assertDatabaseHas('notifications', [
        'user_id' => $user->id,
        'target_id' => $correctionId,
        'type' => 'koreksi_lupa_pulang_submitted',
    ]);

    $this->assertCount(1, Storage::disk('public')->allFiles('bukti_koreksi_pulang'));

    $this->getJson('/api/v1/absensi/me')
        ->assertOk()
        ->assertJsonPath('data.0.can_request_checkout_correction', false)
        ->assertJsonPath('data.0.koreksi_lupa_pulang.status_approval', 'pending');
});

it('rejects attendance older than 30 days', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout(
        $user,
        Carbon::parse('2026-10-04 08:00:00', 'Asia/Jakarta')
    );

    $this->postJson('/api/v1/absensi/pengajuan-lupa-pulang', forgottenCheckoutPayload($attendance))
        ->assertUnprocessable()
        ->assertJsonPath('success', false);

    $this->assertDatabaseCount('absensis', 1);
});

it('allows the previous automatic 17:00 checkout placeholder', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout(
        $user,
        Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta')
    );
    $attendance->update([
        'check_out_at' => Carbon::parse('2026-11-03 17:00:00', 'Asia/Jakarta'),
    ]);

    $this->postJson('/api/v1/absensi/pengajuan-lupa-pulang', forgottenCheckoutPayload($attendance))
        ->assertCreated()
        ->assertJsonPath('success', true);

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'check_out_at' => '2026-11-03 17:00:00',
    ]);
});

it('prevents duplicate active checkout corrections', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout(
        $user,
        Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta')
    );
    Absensi::create([
        'user_id' => $user->id,
        'parent_id' => $attendance->id,
        'status' => 'hadir',
        'tipe' => 'koreksi lupa absen pulang',
        'status_approval' => 'pending',
        'check_in_at' => $attendance->check_in_at,
        'check_out_at' => Carbon::parse('2026-11-03 17:00:00', 'Asia/Jakarta'),
    ]);

    $this->postJson('/api/v1/absensi/pengajuan-lupa-pulang', forgottenCheckoutPayload($attendance))
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    $this->assertDatabaseCount('absensis', 2);
});

it('calculates an organic attendance salary without using an instance context', function () {
    $salary = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        false,
        Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'),
        Carbon::parse('2026-11-03 17:00:00', 'Asia/Jakarta'),
        'organik'
    );

    expect($salary['base_salary'])->toBe(150000.0);
});
