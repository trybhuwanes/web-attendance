<?php

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
it('links attendances to employees', function () {
    $employee = Employee::factory()->create();
    $attendance = Attendance::factory()->for($employee)->create();

    expect($attendance->employee->is($employee))->toBeTrue()
        ->and($employee->attendances)->toHaveCount(1)
        ->and($employee->attendances->first()->is($attendance))->toBeTrue();
});

it('casts employee and attendance attributes', function () {
    $employee = Employee::factory()->create(['is_active' => true]);
    $attendance = Attendance::factory()->present()->create();

    expect($employee->is_active)->toBeBool()->toBeTrue()
        ->and($attendance->date)->toBeInstanceOf(\DateTimeInterface::class)
        ->and($attendance->check_in_at)->toBeInstanceOf(\DateTimeInterface::class)
        ->and($attendance->check_out_at)->toBeInstanceOf(\DateTimeInterface::class);
});

it('casts holiday date attributes', function () {
    $holiday = Holiday::factory()->create();

    expect($holiday->date)->toBeInstanceOf(\DateTimeInterface::class);
});
