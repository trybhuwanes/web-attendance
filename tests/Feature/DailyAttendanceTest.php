<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('creates a check-in once per day in Asia/Jakarta', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-02 08:15:00', 'Asia/Jakarta'));

    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id]);

    Livewire::actingAs($user)
        ->test('pages::attendance.index')
        ->call('checkIn')
        ->call('checkIn')
        ->assertHasErrors(['attendance']);

    expect(Attendance::query()->count())->toBe(1)
        ->and(Attendance::query()->first()->date->toDateString())->toBe('2026-02-02');
});

it('requires check-in before check-out and allows check-out after', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-02 17:05:00', 'Asia/Jakarta'));

    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id]);

    Livewire::actingAs($user)
        ->test('pages::attendance.index')
        ->call('checkOut')
        ->assertHasErrors(['attendance'])
        ->call('checkIn')
        ->call('checkOut')
        ->assertHasNoErrors();

    $attendance = Attendance::query()->first();

    expect($attendance->check_in_at)->not->toBeNull()
        ->and($attendance->check_out_at)->not->toBeNull();
});
