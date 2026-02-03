<?php

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('allows an employee to submit a sick request for a date', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id, 'role' => 'employee']);

    Livewire::actingAs($user)
        ->test('pages::attendance.index')
        ->set('requestDate', '2026-01-12')
        ->set('requestType', 'sick')
        ->call('submitRequest')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('attendance_requests', [
        'employee_id' => $employee->id,
        'type' => 'sick',
        'status' => 'pending',
    ]);
});

it('prevents duplicate requests for the same date', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id, 'role' => 'employee']);

    AttendanceRequest::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-12',
    ]);

    Livewire::actingAs($user)
        ->test('pages::attendance.index')
        ->set('requestDate', '2026-01-12')
        ->set('requestType', 'leave')
        ->call('submitRequest')
        ->assertHasErrors(['requestDate']);
});

it('shows request history on the employee attendance page', function () {
    $employee = Employee::factory()->create();
    $user = User::factory()->create(['employee_id' => $employee->id, 'role' => 'employee']);

    AttendanceRequest::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-20',
        'type' => 'leave',
        'status' => 'approved',
    ]);

    Livewire::actingAs($user)
        ->test('pages::attendance.index')
        ->assertSee('20 Jan 2026')
        ->assertSee('Leave')
        ->assertSee('Approved');
});

it('allows admin to approve requests and create attendance', function () {
    Carbon::setTestNow(Carbon::parse('2026-01-12 09:00:00', 'Asia/Jakarta'));

    $admin = User::factory()->create(['role' => 'admin']);
    $employee = Employee::factory()->create();
    $request = AttendanceRequest::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-01-12',
        'type' => 'leave',
        'status' => 'pending',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::attendance.requests')
        ->call('approve', $request->id);

    $this->assertDatabaseHas('attendance_requests', [
        'id' => $request->id,
        'status' => 'approved',
    ]);

    $this->assertDatabaseHas('attendances', [
        'employee_id' => $employee->id,
        'status' => 'leave',
    ]);

    $attendance = Attendance::query()->where('employee_id', $employee->id)->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->check_in_at)->toBeNull()
        ->and($attendance->check_out_at)->toBeNull();
});
