<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\AttendanceRequest>
 */
class AttendanceRequestFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = Carbon::instance(fake()->dateTimeBetween('-2 weeks', 'now'))->startOfDay();

        return [
            'employee_id' => Employee::factory(),
            'date' => $date->toDateString(),
            'type' => fake()->randomElement(['sick', 'leave']),
            'status' => 'pending',
        ];
    }
}
