<?php

use App\Models\Employee;
use App\Models\User;
use Livewire\Livewire;

it('creates a user linked to an employee', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = Employee::factory()->create();

    Livewire::actingAs($admin)
        ->test('pages::users.create')
        ->set('employee_id', $employee->id)
        ->set('password', 'password123')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('users', [
        'employee_id' => $employee->id,
        'email' => $employee->email,
        'role' => 'employee',
    ]);
});

it('auto-fills name and email from the selected employee', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $employee = Employee::factory()->create([
        'name' => 'Dina Flores',
        'email' => 'dina@example.com',
    ]);

    Livewire::actingAs($admin)
        ->test('pages::users.create')
        ->set('employee_id', $employee->id)
        ->assertSet('name', 'Dina Flores')
        ->assertSet('email', 'dina@example.com');
});
