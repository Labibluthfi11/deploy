<?php

use App\Exceptions\FaceDetectionUnavailableException;
use App\Models\Absensi;
use App\Models\User;
use App\Services\FaceDetector;
use Carbon\Carbon;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

uses(TestCase::class);

beforeEach(function () {
    Carbon::setTestNow(Carbon::parse('2026-11-05 15:00:00', 'Asia/Jakarta'));
    Storage::fake('public');

    Schema::dropIfExists('izin_keluars');
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
        $table->string('kategori_absensi')->nullable();
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
        $table->timestamp('check_out_at')->nullable();
        $table->string('file_bukti')->nullable();
        $table->string('foto_masuk')->nullable();
        $table->string('foto_pulang')->nullable();
        $table->string('lokasi_masuk')->nullable();
        $table->string('lokasi_pulang')->nullable();
        $table->integer('late_minutes')->nullable();
        $table->timestamps();
    });

    Schema::create('izin_keluars', function (Blueprint $table) {
        $table->id();
        $table->unsignedBigInteger('user_id');
        $table->string('status_izin')->default('selesai');
        $table->boolean('is_pelanggaran')->default(false);
        $table->string('tipe_izin')->nullable();
        $table->timestamp('waktu_keluar')->nullable();
        $table->timestamp('waktu_kembali')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Carbon::setTestNow();
    Schema::dropIfExists('izin_keluars');
    Schema::dropIfExists('absensis');
    Schema::dropIfExists('users');
});

it('rejects a check-in photo when no face is detected', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $detector = Mockery::mock(FaceDetector::class);
    $detector->shouldReceive('containsFace')->once()->andReturn(false);
    $this->app->instance(FaceDetector::class, $detector);

    $this->postJson('/api/v1/absensi/masuk', [
        'foto' => UploadedFile::fake()->image('check-in.jpg'),
        'lat' => -6.2,
        'lng' => 106.8,
        'status' => 'hadir',
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('foto');

    expect(Storage::disk('public')->allFiles('absensi_foto'))->toBe([]);
    expect(Absensi::count())->toBe(0);
});

it('does not save a check-in when face detection is unavailable', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    $detector = Mockery::mock(FaceDetector::class);
    $detector->shouldReceive('containsFace')
        ->once()
        ->andThrow(new FaceDetectionUnavailableException);
    $this->app->instance(FaceDetector::class, $detector);

    $this->postJson('/api/v1/absensi/masuk', [
        'foto' => UploadedFile::fake()->image('check-in.jpg'),
        'lat' => -6.2,
        'lng' => 106.8,
        'status' => 'hadir',
    ])->assertStatus(503);

    expect(Storage::disk('public')->allFiles('absensi_foto'))->toBe([]);
    expect(Absensi::count())->toBe(0);
});

it('rejects a check-out photo when no face is detected', function () {
    $user = User::factory()->create();
    Sanctum::actingAs($user);

    Absensi::create([
        'user_id' => $user->id,
        'status' => 'hadir',
        'status_approval' => 'approved',
        'check_in_at' => Carbon::parse('2026-11-05 08:00:00', 'Asia/Jakarta'),
    ]);

    $detector = Mockery::mock(FaceDetector::class);
    $detector->shouldReceive('containsFace')->once()->andReturn(false);
    $this->app->instance(FaceDetector::class, $detector);

    $this->postJson('/api/v1/absensi/pulang', [
        'foto' => UploadedFile::fake()->image('check-out.jpg'),
        'lat' => -6.2,
        'lng' => 106.8,
    ])->assertUnprocessable()
        ->assertJsonValidationErrors('foto');

    expect(Storage::disk('public')->allFiles('absensi_foto'))->toBe([]);
    expect(Absensi::whereNotNull('check_out_at')->count())->toBe(0);
});
