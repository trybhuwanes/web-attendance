<?php

use App\Models\Employee;
use Livewire\Livewire;

it('lists employees and filters by search', function () {
    Employee::factory()->create([
        'employee_code' => 'EMP-0001',
        'name' => 'Alice Johnson',
        'email' => 'alice@example.com',
    ]);

    Employee::factory()->create([
        'employee_code' => 'EMP-0002',
        'name' => 'Bob Smith',
        'email' => 'bob@example.com',
    ]);

    Livewire::test('pages::employees.index')
        ->assertSee('Alice Johnson')
        ->assertSee('Bob Smith')
        ->set('search', 'Alice')
        ->assertSee('Alice Johnson')
        ->assertDontSee('Bob Smith');
});

it('creates an employee', function () {
    Livewire::test('pages::employees.create')
        ->set('employee_code', 'EMP-1001')
        ->set('name', 'Cory Blake')
        ->set('email', 'cory@example.com')
        ->set('department', 'Operations')
        ->set('position', 'Coordinator')
        ->set('is_active', true)
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'employee_code' => 'EMP-1001',
        'name' => 'Cory Blake',
        'email' => 'cory@example.com',
        'department' => 'Operations',
        'position' => 'Coordinator',
        'is_active' => true,
    ]);
});

it('updates an employee', function () {
    $employee = Employee::factory()->create([
        'employee_code' => 'EMP-2001',
        'name' => 'Dina Lane',
    ]);

    Livewire::test('pages::employees.edit', ['employee' => $employee])
        ->set('name', 'Dina Torres')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('employees', [
        'id' => $employee->id,
        'name' => 'Dina Torres',
    ]);
});

it('toggles employee active status', function () {
    $employee = Employee::factory()->create(['is_active' => true]);

    Livewire::test('pages::employees.index')
        ->call('toggleActive', $employee->id);

    expect($employee->refresh()->is_active)->toBeFalse();
});
