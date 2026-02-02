<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        $employee = Employee::factory()->create();

        User::factory()->create([
            'employee_id' => $employee->id,
            'name' => 'Test User',
            'email' => 'test@example.com',
            'role' => 'admin',
        ]);

        $this->call([
            EmployeeSeeder::class,
            AttendanceSeeder::class,
            HolidaySeeder::class,
        ]);
    }
}
