<?php

use App\Models\Absensi;
use App\Models\ScheduledLembur;
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
    Carbon::setTestNow(Carbon::parse('2026-11-04 15:00:00', 'Asia/Jakarta'));
    Storage::fake('public');
    $this->withoutMiddleware(\Illuminate\Routing\Middleware\ThrottleRequests::class);

    Schema::dropIfExists('notifications');
    Schema::dropIfExists('activity_logs');
    Schema::dropIfExists('holidays');
    Schema::dropIfExists('scheduled_lemburs');
    Schema::dropIfExists('izin_keluars');
    Schema::dropIfExists('employee_shift_overrides');
    Schema::dropIfExists('employee_work_assignments');
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
        $table->timestamp('check_in_at')->nullable();
        $table->timestamp('check_out_at')->nullable();
        $table->timestamp('approved_at')->nullable();
        $table->timestamp('rejected_at')->nullable();
        $table->string('foto_masuk')->nullable();
        $table->date('end_date')->nullable();
        $table->unsignedInteger('total_days')->nullable();
        $table->unsignedInteger('hari_potong_cuti')->default(0);
        $table->unsignedInteger('hari_unpaid')->default(0);
        $table->string('jam_pulang_rencana')->nullable();
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
        $table->integer('overtime_minutes')->nullable();
        $table->boolean('is_kurang_8_jam')->nullable();
        $table->boolean('is_weekend_overtime')->nullable();
        $table->boolean('is_mocked')->nullable();
        $table->string('lokasi_masuk')->nullable();
        $table->unsignedBigInteger('work_shift_id')->nullable();
        $table->unsignedBigInteger('pay_scheme_id')->nullable();
        $table->json('payroll_snapshot')->nullable();
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

    Schema::create('scheduled_lemburs', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->date('tanggal_lembur');
        $table->text('keterangan');
        $table->string('foto_bukti')->nullable();
        $table->string('status')->default('pending');
        $table->integer('current_approval_level')->default(1);
        $table->boolean('is_reminder_sent')->default(false);
        $table->timestamps();
    });

    Schema::create('izin_keluars', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
    });

    Schema::create('employee_work_assignments', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('work_shift_id')->nullable();
        $table->unsignedBigInteger('pay_scheme_id')->nullable();
        $table->date('effective_from');
        $table->date('effective_until')->nullable();
        $table->string('schedule_mode')->nullable();
        $table->string('rotation_group')->nullable();
    });

    Schema::create('employee_shift_overrides', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('work_shift_id')->nullable();
        $table->date('work_date');
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
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('activity_logs');
    Schema::dropIfExists('holidays');
    Schema::dropIfExists('scheduled_lemburs');
    Schema::dropIfExists('izin_keluars');
    Schema::dropIfExists('employee_shift_overrides');
    Schema::dropIfExists('employee_work_assignments');
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

function retroactiveAttendancePayload(string $type): array
{
    return [
        'jenis_pengajuan' => $type,
        'tanggal' => '2026-11-03',
        'jam_masuk' => $type === 'lupa_keduanya' ? '08:15' : null,
        'jam_pulang' => $type === 'lupa_keduanya' ? '17:00' : null,
        'keterangan' => 'Lupa melakukan absensi pada hari kerja.',
        'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ];
}

it('creates a pending missed check-in and checkout request with one photo proof', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);

    $this->postJson('/api/v1/absensi/pengajuan-lampau', retroactiveAttendancePayload('lupa_keduanya'))
        ->assertCreated()
        ->assertJsonPath('success', true)
        ->assertJsonPath('data.tipe', 'koreksi lupa absen')
        ->assertJsonPath('data.submission_type', 'koreksi_lupa_keduanya')
        ->assertJsonPath('data.status_approval', 'pending');

    $this->assertDatabaseHas('absensis', [
        'user_id' => $user->id,
        'submission_type' => 'koreksi_lupa_keduanya',
        'status_approval' => 'pending',
        'check_in_at' => '2026-11-03 08:15:00',
        'check_out_at' => '2026-11-03 17:00:00',
    ]);
    $this->assertCount(1, Storage::disk('public')->allFiles('bukti_koreksi_absensi'));
});

it('requires a photo proof and only allows requests within the last 30 days', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    foreach ([
        [],
        ['file_bukti' => null],
        ['file_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
        ['file_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
    ] as $evidence) {
        $payload = retroactiveAttendancePayload('lupa_keduanya');
        unset($payload['file_bukti']);
        $this->postJson('/api/v1/absensi/pengajuan-lampau', array_merge($payload, $evidence))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_bukti']);
    }

    $payload = retroactiveAttendancePayload('lupa_keduanya');
    $payload['tanggal'] = '2026-10-04';
    $this->postJson('/api/v1/absensi/pengajuan-lampau', $payload)
        ->assertUnprocessable()
        ->assertJsonValidationErrors(['tanggal']);

    $this->assertDatabaseCount('absensis', 0);
});

it('does not accept forgotten entry or checkout-only types on the new endpoint', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    foreach (['lupa_masuk', 'lupa_pulang'] as $type) {
        $this->postJson('/api/v1/absensi/pengajuan-lampau', retroactiveAttendancePayload($type))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['jenis_pengajuan']);
    }

    $this->assertDatabaseCount('absensis', 0);
});

it('rejects a missed-both request when attendance already exists for that date', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));

    $this->postJson('/api/v1/absensi/pengajuan-lampau', retroactiveAttendancePayload('lupa_keduanya'))
        ->assertStatus(409)
        ->assertJsonPath('success', false);

    $this->assertDatabaseCount('absensis', 1);
});

it('creates a pending retrospective sick leave request with one photo proof', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);

    $payload = retroactiveAttendancePayload('sakit');
    $payload['keterangan'] = 'Sakit dan tidak dapat masuk kerja.';
    $this->postJson('/api/v1/absensi/pengajuan-lampau', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'sakit')
        ->assertJsonPath('data.tipe', 'sakit')
        ->assertJsonPath('data.submission_type', 'sakit')
        ->assertJsonPath('data.status_approval', 'pending');

    $this->assertDatabaseHas('absensis', [
        'user_id' => $user->id,
        'status' => 'sakit',
        'status_approval' => 'pending',
        'check_in_at' => '2026-11-03 00:00:00',
    ]);
    $this->assertCount(1, Storage::disk('public')->allFiles('bukti_sakit_izin'));
});

it('creates a pending retrospective leave request for an organic employee', function () {
    $user = User::factory()->create([
        'work_location' => 'office',
        'employment_type' => 'organik',
        'sisa_cuti' => 10,
    ]);
    Sanctum::actingAs($user);

    $payload = retroactiveAttendancePayload('izin');
    $payload['jenis_izin'] = 'cuti_tahunan';
    $payload['keterangan'] = 'Mengajukan cuti tahunan untuk hari tersebut.';
    $this->postJson('/api/v1/absensi/pengajuan-lampau', $payload)
        ->assertCreated()
        ->assertJsonPath('data.status', 'izin')
        ->assertJsonPath('data.submission_type', 'cuti_tahunan')
        ->assertJsonPath('data.status_approval', 'pending');

    $this->assertDatabaseHas('absensis', [
        'user_id' => $user->id,
        'status' => 'izin',
        'submission_type' => 'cuti_tahunan',
        'status_approval' => 'pending',
        'check_in_at' => '2026-11-03 00:00:00',
        'hari_potong_cuti' => 1,
    ]);
    $this->assertCount(1, Storage::disk('public')->allFiles('bukti_sakit_izin'));
});

it('does not expose the removed forgotten-checkout request endpoint', function () {
    Sanctum::actingAs(User::factory()->create());

    $this->postJson('/api/v1/absensi/pengajuan-lupa-pulang')
        ->assertNotFound();
});

it('lists prior missing checkouts without blocking the next day check-in', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));

    $this->getJson('/api/v1/absensi/missing-checkouts')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.status_approval', 'approved');

    $this->postJson('/api/v1/absensi/masuk', [
        'foto' => UploadedFile::fake()->image('masuk.jpg'),
        'lat' => -6.2,
        'lng' => 106.8,
        'status' => 'hadir',
    ])->assertCreated();
});

it('submits a missed checkout for approval and holds salary until review', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));

    $this->postJson("/api/v1/absensi/{$attendance->id}/checkout-correction", [
        'jenis_koreksi' => 'lupa_absen_pulang',
        'jam_pulang' => '17:00',
        'keterangan' => 'Lupa melakukan absen pulang kemarin.',
        'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ])
        ->assertCreated()
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.submission_type', 'koreksi_lupa_pulang')
        ->assertJsonPath('data.jam_pulang_rencana', '17:00');

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'status_approval' => 'pending',
        'base_salary' => 0,
        'final_salary' => 0,
    ]);
    $this->assertTrue(Storage::disk('public')->exists($attendance->fresh()->file_bukti));
});

it('allows a previously auto-filled checkout to be corrected and holds its salary', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-11-03 17:00:00', 'Asia/Jakarta'),
        'base_salary' => 150000,
        'final_salary' => 150000,
    ]);
    $attendance->forceFill(['catatan_admin' => 'AUTO_CHECKOUT_LUPA_ABSEN'])->save();

    $this->getJson('/api/v1/absensi/missing-checkouts')
        ->assertOk()
        ->assertJsonCount(1, 'data');

    $this->postJson("/api/v1/absensi/{$attendance->id}/checkout-correction", [
        'jenis_koreksi' => 'pulang_lebih_awal',
        'jam_pulang' => '13:00',
        'keterangan' => 'Jam pulang otomatis bukan jam pulang sebenarnya.',
        'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ])
        ->assertCreated()
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.check_out_at', null);

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'status_approval' => 'pending',
        'check_out_at' => null,
        'base_salary' => 0,
        'final_salary' => 0,
    ]);
});

it('applies the verified checkout time and prorated salary on final approval', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
    $employee = User::factory()->create([
        'work_location' => 'office',
        'employment_type' => 'freelance',
    ]);
    $approver = User::factory()->create([
        'role' => 'hrga',
        'work_location' => 'office',
    ]);
    $attendance = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'tipe' => 'koreksi lupa pulang',
        'submission_type' => 'koreksi_lupa_pulang',
        'status_approval' => 'pending',
        'current_approval_level' => 2,
        'workflow_status' => ['manager' => 'approved', 'hrga' => 'pending'],
        'check_in_at' => Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'),
        'jam_pulang_rencana' => '17:00',
        'base_salary' => 0,
        'final_salary' => 0,
    ]);

    $this->actingAs($approver)
        ->from('/admin/approval/hrga')
        ->post("/admin/approval/{$attendance->id}/approve")
        ->assertRedirect('/admin/approval/hrga');

    $attendance->refresh();
    expect($attendance->status_approval)->toBe('approved')
        ->and($attendance->check_out_at->format('Y-m-d H:i'))->toBe('2026-11-03 17:00')
        ->and((float) $attendance->final_salary)->toBeGreaterThan(0);
});

it('shows missing checkouts in the web admin review list', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
    $employee = User::factory()->create(['name' => 'Checkout Review Employee']);
    $admin = User::factory()->create(['role' => 'admin']);
    createAttendanceWithoutCheckout($employee, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));

    $this->actingAs($admin)
        ->get('/admin/absensi/missing-checkouts')
        ->assertOk()
        ->assertSee('Absensi Belum Absen Pulang')
        ->assertSee('Checkout Review Employee');
});

it('shows legacy auto-filled checkouts in the web admin review list', function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);
    $employee = User::factory()->create(['name' => 'Legacy Auto Checkout Employee']);
    $admin = User::factory()->create(['role' => 'admin']);
    $attendance = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-11-03 17:00:00', 'Asia/Jakarta'),
    ]);
    $attendance->forceFill(['catatan_admin' => 'AUTO_CHECKOUT_LUPA_ABSEN'])->save();

    $this->actingAs($admin)
        ->get('/admin/absensi/missing-checkouts')
        ->assertOk()
        ->assertSee('Legacy Auto Checkout Employee')
        ->assertSee('Jam pulang otomatis — perlu verifikasi');
});

it('rejects a checkout correction with a time before check-in', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));

    $this->postJson("/api/v1/absensi/{$attendance->id}/checkout-correction", [
        'jenis_koreksi' => 'pulang_lebih_awal',
        'jam_pulang' => '08:00',
        'keterangan' => 'Saya pulang lebih awal pada hari tersebut.',
    ])->assertUnprocessable();
});

it('resubmits a rejected half-day leave with corrected time and explanation', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'rejected',
        'rejected_by' => 'manager',
        'check_in_at' => Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'),
        'jam_pulang_rencana' => '12:00',
        'keterangan_izin_sakit' => 'Jam pulang sebelumnya salah.',
        'file_bukti' => Storage::disk('public')->putFile('bukti_sakit_izin', UploadedFile::fake()->image('lama.jpg')),
    ]);

    $this->postJson("/api/v1/absensi/izin/{$attendance->id}/resubmit", [
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Jam pulang yang benar adalah pukul satu siang.',
    ])
        ->assertOk()
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.jam_pulang_rencana', '13:00');

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'status_approval' => 'pending',
        'jam_pulang_rencana' => '13:00',
    ]);
    $this->assertTrue(Storage::disk('public')->exists($attendance->fresh()->file_bukti));
});

it('allows an organic employee to submit a half-day request after checking in', function () {
    $user = User::factory()->create([
        'work_location' => 'office',
        'employment_type' => 'organik',
    ]);
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'));

    $this->postJson('/api/v1/absensi/izin', [
        'status' => 'izin',
        'catatan_admin' => 'izin_pulang_cepat',
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Ada keperluan keluarga setelah jam makan siang.',
        'file_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ])
        ->assertCreated()
        ->assertJsonPath('data.status_approval', 'pending')
        ->assertJsonPath('data.submission_type', 'izin_pulang_cepat')
        ->assertJsonPath('data.jam_pulang_rencana', '13:00');

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'status_approval' => 'pending',
        'base_salary' => 0,
    ]);
    $this->assertTrue(Storage::disk('public')->exists($attendance->fresh()->file_bukti));
});

it('requires valid evidence for both checkout correction types without changing attendance on failure', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);

    foreach (['lupa_absen_pulang', 'pulang_lebih_awal'] as $type) {
        $attendance = createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-03 08:15:00', 'Asia/Jakarta'));
        $payload = [
            'jenis_koreksi' => $type,
            'jam_pulang' => $type === 'lupa_absen_pulang' ? '17:00' : '13:00',
            'keterangan' => 'Mohon koreksi jam pulang pada tanggal tersebut.',
        ];

        foreach ([
            [],
            ['file_bukti' => null],
            ['file_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
            ['file_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
        ] as $evidence) {
            $this->postJson("/api/v1/absensi/{$attendance->id}/checkout-correction", array_merge($payload, $evidence))
                ->assertUnprocessable()
                ->assertJsonValidationErrors(['file_bukti']);
        }

        $this->assertDatabaseHas('absensis', [
            'id' => $attendance->id,
            'status_approval' => 'approved',
            'file_bukti' => null,
        ]);
    }
});

it('requires and stores evidence for scheduled overtime submissions', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $payload = [
        'tanggal_lembur' => '2026-11-05',
        'keterangan' => 'Mempersiapkan pekerjaan untuk jadwal besok.',
    ];
    foreach ([
        [],
        ['foto_bukti' => null],
        ['foto_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
        ['foto_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
    ] as $evidence) {
        $this->postJson('/api/v1/absensi/scheduled-lembur', array_merge($payload, $evidence))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto_bukti']);
    }
    $this->assertDatabaseCount('scheduled_lemburs', 0);

    $this->postJson('/api/v1/absensi/scheduled-lembur', array_merge($payload, [
        'foto_bukti' => UploadedFile::fake()->image('bukti.jpg'),
    ]))->assertOk()->assertJsonPath('success', true);

    $record = ScheduledLembur::firstOrFail();
    expect($record->foto_bukti)->not->toBeNull();
    $this->assertTrue(Storage::disk('public')->exists($record->foto_bukti));
});

it('routes scheduled overtime resubmission through the common endpoint using foto_bukti', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $attendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin',
        'status_approval' => 'rejected',
        'check_in_at' => Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'),
    ]);
    $record = ScheduledLembur::create([
        'user_id' => $user->id,
        'tanggal_lembur' => '2026-11-05',
        'keterangan' => 'Pengajuan lembur ditolak.',
        'status' => 'rejected',
    ]);
    expect($record->id)->toBe($attendance->id);
    $payload = [
        'tanggal_lembur' => '2026-11-06',
        'keterangan' => 'Memperbarui jadwal pekerjaan lembur.',
    ];

    foreach ([
        [],
        ['foto_bukti' => null],
        ['foto_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
        ['foto_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
    ] as $evidence) {
        $this->postJson("/api/v1/absensi/resubmit/{$record->id}", array_merge($payload, $evidence))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['foto_bukti']);
    }
    expect($record->fresh()->status)->toBe('rejected');
    expect($attendance->fresh()->status_approval)->toBe('rejected');

    $oldEvidence = UploadedFile::fake()->image('bukti-lama.jpg')->store('absensi_foto', 'public');
    $record->update(['foto_bukti' => $oldEvidence]);
    $this->postJson("/api/v1/absensi/resubmit/{$record->id}", $payload)
        ->assertOk()
        ->assertJsonPath('data.foto_bukti', $oldEvidence);
    expect($record->fresh()->foto_bukti)->toBe($oldEvidence);
    $this->assertTrue(Storage::disk('public')->exists($oldEvidence));

    $replacementOldEvidence = UploadedFile::fake()->image('bukti-lama-kedua.jpg')->store('absensi_foto', 'public');
    $replacementRecord = ScheduledLembur::create([
        'user_id' => $user->id,
        'tanggal_lembur' => '2026-11-05',
        'keterangan' => 'Pengajuan lembur ditolak.',
        'foto_bukti' => $replacementOldEvidence,
        'status' => 'rejected',
    ]);
    $replacement = UploadedFile::fake()->image('bukti-baru.jpg');
    $this->postJson("/api/v1/absensi/resubmit/{$replacementRecord->id}", array_merge($payload, [
        'foto_bukti' => $replacement,
    ]))->assertOk();
    expect($replacementRecord->fresh()->foto_bukti)->not->toBe($replacementOldEvidence);
    $this->assertTrue(Storage::disk('public')->exists($replacementRecord->fresh()->foto_bukti));
    $this->assertFalse(Storage::disk('public')->exists($replacementOldEvidence));
});

it('does not let scheduled overtime resubmit reuse a missing stored file', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);
    $record = ScheduledLembur::create([
        'user_id' => $user->id,
        'tanggal_lembur' => '2026-11-05',
        'keterangan' => 'Pengajuan lembur ditolak.',
        'foto_bukti' => 'absensi_foto/file-yang-tidak-ada.jpg',
        'status' => 'rejected',
    ]);

    $this->postJson("/api/v1/absensi/resubmit/{$record->id}", [
        'tanggal_lembur' => '2026-11-06',
        'keterangan' => 'Memperbarui jadwal pekerjaan lembur.',
    ])->assertUnprocessable()->assertJsonValidationErrors(['foto_bukti']);

    expect($record->fresh()->status)->toBe('rejected')
        ->and($record->fresh()->foto_bukti)->toBe('absensi_foto/file-yang-tidak-ada.jpg');

    $emptyEvidencePath = 'absensi_foto/bukti-kosong.pdf';
    Storage::disk('public')->put($emptyEvidencePath, '');
    $emptyEvidenceRecord = ScheduledLembur::create([
        'user_id' => $user->id,
        'tanggal_lembur' => '2026-11-05',
        'keterangan' => 'Pengajuan lembur ditolak.',
        'foto_bukti' => $emptyEvidencePath,
        'status' => 'rejected',
    ]);

    $this->postJson("/api/v1/absensi/resubmit/{$emptyEvidenceRecord->id}", [
        'tanggal_lembur' => '2026-11-06',
        'keterangan' => 'Memperbarui jadwal pekerjaan lembur.',
    ])->assertUnprocessable()->assertJsonValidationErrors(['foto_bukti']);

    expect($emptyEvidenceRecord->fresh()->status)->toBe('rejected');
});

it('requires valid file_bukti for the early-leave izin submission', function () {
    $user = User::factory()->create(['employment_type' => 'organik', 'work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = createAttendanceWithoutCheckout($user, Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'));
    $payload = [
        'status' => 'izin',
        'catatan_admin' => 'izin_pulang_cepat',
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Ada keperluan keluarga setelah jam makan siang.',
    ];

    foreach ([
        [],
        ['file_bukti' => null],
        ['file_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
        ['file_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
    ] as $evidence) {
        $this->postJson('/api/v1/absensi/izin', array_merge($payload, $evidence))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_bukti']);
    }

    $this->assertDatabaseHas('absensis', [
        'id' => $attendance->id,
        'status_approval' => 'approved',
        'file_bukti' => null,
    ]);
});

it('requires new or actually stored evidence when resubmitting early-leave izin', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'rejected',
        'check_in_at' => Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'),
        'jam_pulang_rencana' => '12:00',
        'keterangan_izin_sakit' => 'Jam pulang sebelumnya salah.',
    ]);
    $payload = [
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Jam pulang yang benar adalah pukul satu siang.',
    ];

    foreach ([
        [],
        ['file_bukti' => null],
        ['file_bukti' => UploadedFile::fake()->create('bukti-kosong.pdf', 0, 'application/pdf')],
        ['file_bukti' => UploadedFile::fake()->create('bukti.txt', 10, 'text/plain')],
    ] as $evidence) {
        $this->postJson("/api/v1/absensi/izin/{$attendance->id}/resubmit", array_merge($payload, $evidence))
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['file_bukti']);
    }
    expect($attendance->fresh()->status_approval)->toBe('rejected');

    $oldEvidence = UploadedFile::fake()->image('bukti-lama.jpg')->store('bukti_sakit_izin', 'public');
    $attendance->update(['file_bukti' => $oldEvidence]);
    $this->postJson("/api/v1/absensi/izin/{$attendance->id}/resubmit", $payload)
        ->assertOk();
    expect($attendance->fresh()->file_bukti)->toBe($oldEvidence)
        ->and($attendance->fresh()->status_approval)->toBe('pending');
    $this->assertTrue(Storage::disk('public')->exists($oldEvidence));

    $newEvidenceAttendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'rejected',
        'check_in_at' => Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'),
    ]);
    $this->postJson("/api/v1/absensi/izin/{$newEvidenceAttendance->id}/resubmit", array_merge($payload, [
        'file_bukti' => UploadedFile::fake()->image('bukti-baru.jpg'),
    ]))->assertOk();
    $this->assertTrue(Storage::disk('public')->exists($newEvidenceAttendance->fresh()->file_bukti));
});

it('does not allow early-leave resubmit to reuse a missing stored file', function () {
    $user = User::factory()->create(['work_location' => 'office']);
    Sanctum::actingAs($user);
    $attendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'rejected',
        'check_in_at' => Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'),
        'file_bukti' => 'bukti_sakit_izin/file-yang-tidak-ada.jpg',
    ]);

    $this->postJson("/api/v1/absensi/izin/{$attendance->id}/resubmit", [
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Jam pulang yang benar adalah pukul satu siang.',
    ])->assertUnprocessable()->assertJsonValidationErrors(['file_bukti']);

    expect($attendance->fresh()->status_approval)->toBe('rejected');

    $emptyEvidencePath = 'bukti_sakit_izin/bukti-kosong.pdf';
    Storage::disk('public')->put($emptyEvidencePath, '');
    $emptyEvidenceAttendance = Absensi::create([
        'user_id' => $user->id,
        'status' => 'izin',
        'tipe' => 'izin',
        'submission_type' => 'izin_pulang_cepat',
        'status_approval' => 'rejected',
        'check_in_at' => Carbon::parse('2026-11-04 08:00:00', 'Asia/Jakarta'),
        'file_bukti' => $emptyEvidencePath,
    ]);

    $this->postJson("/api/v1/absensi/izin/{$emptyEvidenceAttendance->id}/resubmit", [
        'jam_pulang_rencana' => '13:00',
        'keterangan_izin_sakit' => 'Jam pulang yang benar adalah pukul satu siang.',
    ])->assertUnprocessable()->assertJsonValidationErrors(['file_bukti']);

    expect($emptyEvidenceAttendance->fresh()->status_approval)->toBe('rejected');
});

it('does not assign a full-shift salary when checkout is missing', function () {
    $salary = Absensi::calculateSalary(
        0,
        'hadir',
        null,
        false,
        Carbon::parse('2026-11-03 08:00:00', 'Asia/Jakarta'),
        null,
        'organik'
    );

    expect($salary['base_salary'])->toBe(0.0)
        ->and($salary['final_salary'])->toBe(0.0);
});
