<?php

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('shows admin dashboard metrics', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 09:00:00', 'Asia/Jakarta'));

    $admin = User::factory()->create(['role' => 'admin']);
    $sales = Employee::factory()->create(['department' => 'Sales']);
    $hr = Employee::factory()->create(['department' => 'HR']);

    Attendance::factory()->create([
        'employee_id' => $sales->id,
        'date' => '2026-01-10',
        'status' => 'present',
        'check_in_at' => Carbon::parse('2026-01-10 08:00:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-01-10 17:00:00', 'Asia/Jakarta'),
    ]);

    Attendance::factory()->create([
        'employee_id' => $hr->id,
        'date' => '2026-01-10',
        'status' => 'absent',
    ]);

    Attendance::factory()->create([
        'employee_id' => $sales->id,
        'date' => '2026-01-11',
        'status' => 'sick',
    ]);

    AttendanceRequest::factory()->create([
        'employee_id' => $sales->id,
        'date' => '2026-01-12',
        'type' => 'leave',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::dashboard')
        ->assertSee('Today Present')
        ->assertSee('1')
        ->assertSee('Today Absent')
        ->assertSee('1')
        ->assertSee('Pending Approvals')
        ->assertSee('1')
        ->assertSee('Weekly Attendance Trend')
        ->assertSee('Sales')
        ->assertSee('HR');
});

it('shows employee monthly totals on the dashboard', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-10 09:00:00', 'Asia/Jakarta'));

    $employee = Employee::factory()->create();
    $user = User::factory()->create(['role' => 'employee', 'employee_id' => $employee->id]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-05',
        'status' => 'present',
        'check_in_at' => Carbon::parse('2026-01-05 08:00:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-01-05 17:00:00', 'Asia/Jakarta'),
    ]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-06',
        'status' => 'leave',
    ]);

    Livewire::actingAs($user)
        ->test('pages::dashboard')
        ->assertSee('My Dashboard')
        ->assertSee('Present')
        ->assertSee('Absent')
        ->assertSee('Sick')
        ->assertSee('Leave');
});
