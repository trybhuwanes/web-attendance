<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('shows attendance detail for selected month and employee', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-10 09:00:00', 'Asia/Jakarta'));

    $admin = User::factory()->create(['role' => 'admin']);
    $employee = Employee::factory()->create(['name' => 'Arif Setiawan']);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-05',
        'status' => 'present',
        'check_in_at' => Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-01-05 17:00:00', 'Asia/Jakarta'),
    ]);

    Holiday::factory()->create([
        'date' => '2026-01-02',
        'description' => 'Holiday',
    ]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-05',
        'status' => 'absent',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::attendance.monthly-detail', ['employee' => $employee, 'month' => 1, 'year' => 2026])
        ->assertSee('05 Jan 2026')
        ->assertSee('Present')
        ->assertSee('02 Jan 2026')
        ->assertSee('Holiday')
        ->assertDontSee('05 Feb 2026');
});
