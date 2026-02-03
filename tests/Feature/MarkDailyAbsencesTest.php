<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Artisan;

it('does not mark absences before 16:00 Asia/Jakarta', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 15:30:00', 'Asia/Jakarta'));

    Employee::factory()->create(['is_active' => true]);

    Artisan::call('attendance:mark-absent');

    expect(Attendance::query()->count())->toBe(0);
});

it('skips weekends and holidays', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-11 16:30:00', 'Asia/Jakarta'));

    Employee::factory()->create(['is_active' => true]);

    Artisan::call('attendance:mark-absent');

    expect(Attendance::query()->count())->toBe(0);

    Carbon::setTestNow(Carbon::parse('2026-01-16 16:30:00', 'Asia/Jakarta'));

    Holiday::factory()->create([
        'date' => '2026-01-16',
        'description' => 'Holiday',
    ]);

    Artisan::call('attendance:mark-absent');

    expect(Attendance::query()->count())->toBe(0);
});

it('marks absences for active employees without attendance after 16:00 Asia/Jakarta', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-09 16:30:00', 'Asia/Jakarta'));

    Holiday::query()->delete();

    $activeEmployee = Employee::factory()->create(['is_active' => true]);
    Employee::factory()->create(['is_active' => false]);

    Artisan::call('attendance:mark-absent');

    $attendance = Attendance::query()
        ->where('employee_id', $activeEmployee->id)
        ->whereDate('date', '2026-01-09')
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->status)->toBe('absent');
});

it('does not create a duplicate attendance record for the same date', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 16:30:00', 'Asia/Jakarta'));

    $employee = Employee::factory()->create(['is_active' => true]);

    Attendance::query()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-10',
        'status' => 'present',
        'check_in_at' => Carbon::parse('2026-01-10 08:00:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-01-10 17:00:00', 'Asia/Jakarta'),
    ]);

    Artisan::call('attendance:mark-absent');

    expect(Attendance::query()->where('employee_id', $employee->id)->count())->toBe(1);
});
