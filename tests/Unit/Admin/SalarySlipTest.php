<?php

use App\Models\Absensi;
use App\Models\Notification;
use App\Models\SalarySlip;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    config(['app.key' => 'base64:' . base64_encode(str_repeat('a', 32))]);

    Schema::dropIfExists('salary_slips');
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('activity_logs');
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
        $table->string('id_karyawan')->nullable();
        $table->string('employment_type')->nullable();
        $table->timestamps();
    });

    Schema::create('absensis', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('parent_id')->nullable();
        $table->string('status');
        $table->string('tipe')->nullable();
        $table->string('status_approval')->default('pending');
        $table->timestamp('check_in_at')->nullable();
        $table->decimal('base_salary', 15, 2)->nullable();
        $table->decimal('late_penalty', 15, 2)->nullable();
        $table->integer('overtime_minutes')->nullable();
        $table->decimal('overtime_pay', 15, 2)->nullable();
        $table->decimal('final_salary', 15, 2)->nullable();
        $table->decimal('adjustment_salary', 15, 2)->default(0);
        $table->json('payroll_snapshot')->nullable();
        $table->timestamps();
    });

    Schema::create('salary_slips', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->unsignedBigInteger('issued_by')->nullable();
        $table->date('period_start');
        $table->date('period_end');
        $table->string('employment_type');
        $table->unsignedInteger('attendance_days')->default(0);
        $table->unsignedInteger('overtime_minutes')->default(0);
        $table->decimal('base_salary', 15, 2)->default(0);
        $table->decimal('overtime_pay', 15, 2)->default(0);
        $table->decimal('late_penalty', 15, 2)->default(0);
        $table->decimal('adjustment_salary', 15, 2)->default(0);
        $table->decimal('net_salary', 15, 2)->default(0);
        $table->json('daily_breakdown');
        $table->timestamp('issued_at');
        $table->timestamps();
        $table->unique(['user_id', 'period_start', 'period_end']);
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
    Schema::dropIfExists('salary_slips');
    Schema::dropIfExists('notifications');
    Schema::dropIfExists('activity_logs');
    Schema::dropIfExists('absensis');
    Schema::dropIfExists('users');
});

it('previews and issues an immutable slip from approved parent attendance and adjustment', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);

    $parent = Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => '2026-11-14 08:00:00',
        'base_salary' => 150000,
        'late_penalty' => 10000,
        'overtime_minutes' => 60,
        'overtime_pay' => 25000,
        'final_salary' => 165000,
        'adjustment_salary' => 5000,
    ]);

    Absensi::create([
        'user_id' => $employee->id,
        'parent_id' => $parent->id,
        'status' => 'hadir',
        'tipe' => 'lembur',
        'status_approval' => 'approved',
        'check_in_at' => '2026-11-14 08:00:00',
        'overtime_minutes' => 60,
        'overtime_pay' => 25000,
        'final_salary' => 25000,
    ]);

    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'status_approval' => 'rejected',
        'check_in_at' => '2026-11-13 08:00:00',
        'base_salary' => 150000,
        'final_salary' => 150000,
    ]);

    $input = [
        'start_date' => '2026-11-01',
        'end_date' => '2026-11-14',
        'recipient_mode' => 'selected',
        'user_ids' => [$employee->id],
    ];

    $this->actingAs($admin)
        ->post(route('admin.salary-slips.preview'), $input)
        ->assertOk()
        ->assertSee('170.000')
        ->assertSee('Siap diterbitkan');

    $this->actingAs($admin)
        ->post(route('admin.salary-slips.issue'), $input)
        ->assertRedirect(route('admin.salary-slips.index'))
        ->assertSessionHas('success');

    $slip = SalarySlip::query()->sole();

    expect((int) $slip->user_id)->toBe($employee->id)
        ->and((float) $slip->net_salary)->toBe(170000.0)
        ->and((float) $slip->overtime_pay)->toBe(25000.0)
        ->and((float) $slip->adjustment_salary)->toBe(5000.0)
        ->and(count($slip->daily_breakdown))->toBe(1)
        ->and(Notification::where('user_id', $employee->id)->count())->toBe(1);
});

it('only allows freelance and borongan recipients', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $organicEmployee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'organik',
    ]);

    $this->actingAs($admin)
        ->post(route('admin.salary-slips.preview'), [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-14',
            'recipient_mode' => 'selected',
            'user_ids' => [$organicEmployee->id],
        ])
        ->assertSessionHasErrors('user_ids');

    expect(SalarySlip::count())->toBe(0);
});

it('limits publishing access to admin and super admin roles', function () {
    $manager = User::factory()->create(['role' => 'manager']);

    $this->actingAs($manager)
        ->get(route('admin.salary-slips.index'))
        ->assertForbidden();
});

it('issues to all freelance and borongan employees while excluding organic and interns', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $freelance = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);
    $borongan = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'borongan',
    ]);
    $organicEmployee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'organik',
    ]);
    $intern = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'magang',
    ]);

    foreach ([$freelance, $borongan, $organicEmployee, $intern] as $employee) {
        Absensi::create([
            'user_id' => $employee->id,
            'status' => 'hadir',
            'status_approval' => 'approved',
            'check_in_at' => '2026-11-10 08:00:00',
            'base_salary' => 100000,
            'final_salary' => 100000,
        ]);
    }

    $this->actingAs($admin)
        ->post(route('admin.salary-slips.issue'), [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-14',
            'recipient_mode' => 'all',
        ])
        ->assertRedirect(route('admin.salary-slips.index'))
        ->assertSessionHas('success');

    expect(SalarySlip::count())->toBe(2)
        ->and(SalarySlip::whereIn('user_id', [$freelance->id, $borongan->id])->count())->toBe(2)
        ->and(Notification::count())->toBe(2);
});

it('does not issue a second slip whose period overlaps an existing slip', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'borongan',
    ]);

    Absensi::create([
        'user_id' => $employee->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => '2026-11-10 08:00:00',
        'base_salary' => 125000,
        'final_salary' => 125000,
    ]);

    SalarySlip::create([
        'user_id' => $employee->id,
        'period_start' => '2026-11-08',
        'period_end' => '2026-11-20',
        'employment_type' => 'borongan',
        'daily_breakdown' => [],
        'issued_at' => now(),
    ]);

    $this->actingAs($admin)
        ->post(route('admin.salary-slips.issue'), [
            'start_date' => '2026-11-01',
            'end_date' => '2026-11-14',
            'recipient_mode' => 'selected',
            'user_ids' => [$employee->id],
        ])
        ->assertRedirect(route('admin.salary-slips.index'))
        ->assertSessionHas('error');

    expect(SalarySlip::count())->toBe(1)
        ->and(Notification::where('user_id', $employee->id)->count())->toBe(0);
});

it('returns only the authenticated employees slips and hides other employees slips', function () {
    $employee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'borongan',
    ]);
    $otherEmployee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'freelance',
    ]);

    $slip = SalarySlip::create([
        'user_id' => $employee->id,
        'period_start' => '2026-11-01',
        'period_end' => '2026-11-14',
        'employment_type' => 'borongan',
        'daily_breakdown' => [],
        'issued_at' => now(),
    ]);
    $otherSlip = SalarySlip::create([
        'user_id' => $otherEmployee->id,
        'period_start' => '2026-11-01',
        'period_end' => '2026-11-14',
        'employment_type' => 'freelance',
        'daily_breakdown' => [],
        'issued_at' => now(),
    ]);

    Sanctum::actingAs($employee);

    $this->getJson('/api/v1/salary-slips')
        ->assertOk()
        ->assertJsonCount(1, 'data')
        ->assertJsonPath('data.0.id', $slip->id);

    $this->getJson("/api/v1/salary-slips/{$otherSlip->id}")
        ->assertNotFound();

    $organicEmployee = User::factory()->create([
        'role' => 'user',
        'employment_type' => 'organik',
    ]);
    Sanctum::actingAs($organicEmployee);

    $this->getJson('/api/v1/salary-slips')
        ->assertForbidden();
});
