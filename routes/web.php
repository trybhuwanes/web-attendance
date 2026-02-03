<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
})->name('home');

Route::livewire('dashboard', 'pages::dashboard')
    ->middleware(['auth', 'verified'])
    ->name('dashboard');

Route::middleware(['auth', 'verified', 'role:employee'])->group(function () {
    Route::livewire('attendance', 'pages::attendance.index')->name('attendance.index');
    Route::livewire('approval', 'pages::attendance.employee-requests')->name('attendance.approval');
});

Route::middleware(['auth', 'verified', 'role:admin'])->group(function () {
    Route::livewire('employees', 'pages::employees.index')->name('employees.index');
    Route::livewire('employees/create', 'pages::employees.create')->name('employees.create');
    Route::livewire('employees/{employee}/edit', 'pages::employees.edit')->name('employees.edit');
    Route::livewire('holidays', 'pages::holidays.index')->name('holidays.index');
    Route::livewire('attendance/report', 'pages::attendance.report')->name('attendance.report');
    Route::livewire('attendance/report/monthly', 'pages::attendance.monthly-report')->name('attendance.report.monthly');
    Route::livewire('attendance/report/monthly/{employee}', 'pages::attendance.monthly-detail')->name('attendance.report.monthly.detail');
    Route::livewire('attendance/requests', 'pages::attendance.requests')->name('attendance.requests');
    Route::livewire('users/create', 'pages::users.create')->name('users.create');
});

require __DIR__.'/settings.php';
