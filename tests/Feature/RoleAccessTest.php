<?php

use App\Models\User;

it('allows employees to access attendance but not admin pages', function () {
    $employee = User::factory()->create(['role' => 'employee']);

    $this->actingAs($employee)
        ->get(route('attendance.index'))
        ->assertSuccessful();

    $this->actingAs($employee)
        ->get(route('employees.index'))
        ->assertForbidden();

    $this->actingAs($employee)
        ->get(route('attendance.report'))
        ->assertForbidden();
});

it('allows admins to access admin pages but not attendance check-in', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get(route('employees.index'))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('attendance.report'))
        ->assertSuccessful();

    $this->actingAs($admin)
        ->get(route('attendance.index'))
        ->assertForbidden();
});
