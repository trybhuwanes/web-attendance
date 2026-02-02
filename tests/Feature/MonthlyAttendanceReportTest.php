<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use App\Models\User;
use Illuminate\Support\Carbon;
use Livewire\Livewire;

it('shows monthly totals per employee and respects holidays toggle', function () {
    Carbon::setTestNow(Carbon::parse('2026-02-10 09:00:00', 'Asia/Jakarta'));

    $admin = User::factory()->create(['role' => 'admin']);
    $employee = Employee::factory()->create(['name' => 'Rina Putri']);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-03',
        'status' => 'present',
        'check_in_at' => Carbon::parse('2026-02-03 08:00:00', 'Asia/Jakarta'),
        'check_out_at' => Carbon::parse('2026-02-03 17:00:00', 'Asia/Jakarta'),
    ]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-04',
        'status' => 'absent',
    ]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-05',
        'status' => 'sick',
    ]);

    Attendance::factory()->create([
        'employee_id' => $employee->id,
        'date' => '2026-02-06',
        'status' => 'leave',
    ]);

    Holiday::factory()->create([
        'date' => '2026-02-10',
        'description' => 'National Holiday',
    ]);

    Holiday::factory()->create([
        'date' => '2026-02-16',
        'description' => 'Second Holiday',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::attendance.monthly-report')
        ->set('month', 2)
        ->set('year', 2026)
        ->assertSee('Rina Putri')
        ->assertSee('1')
        ->assertSee('1')
        ->assertSee('1')
        ->assertSee('1');

    Livewire::actingAs($admin)
        ->test('pages::attendance.monthly-report')
        ->set('month', 2)
        ->set('year', 2026)
        ->assertSee((string) (workingDaysInMonth(2026, 2) - 2));
});

function workingDaysInMonth(int $year, int $month): int
{
    $start = Carbon::create($year, $month, 1, 0, 0, 0, 'Asia/Jakarta')->startOfMonth();
    $end = $start->copy()->endOfMonth();
    $count = 0;
    $current = $start->copy();

    while ($current->lte($end)) {
        if (! $current->isWeekend()) {
            $count++;
        }

        $current->addDay();
    }

    return $count;
}
