<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::view('dashboard', 'dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:employee'])->group(function () {
    Route::livewire('attendance', 'pages::attendance.index')->name('attendance.index');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::livewire('employees', 'pages::employees.index')->name('employees.index');
    Route::livewire('employees/create', 'pages::employees.create')->name('employees.create');
    Route::livewire('employees/{employee}/edit', 'pages::employees.edit')->name('employees.edit');
    Route::livewire('attendance/report', 'pages::attendance.report')->name('attendance.report');
    Route::livewire('users/create', 'pages::users.create')->name('users.create');
});

require __DIR__.'/settings.php';
