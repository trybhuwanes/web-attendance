<?php

namespace Database\Factories;

use App\Models\Employee;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Attendance>
 */
class AttendanceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $status = fake()->randomElement(['present', 'sick', 'leave', 'absent']);
        $checkInAt = null;
        $checkOutAt = null;
        $date = Carbon::instance(fake()->dateTimeBetween('-1 week', 'now'))->startOfDay();

        if ($status === 'present') {
            $checkInAt = $date->copy()
                ->addHours(fake()->numberBetween(7, 10))
                ->addMinutes(fake()->randomElement([0, 15, 30, 45]));

            $checkOutAt = $checkInAt->copy()->addHours(fake()->numberBetween(7, 10));
        }

        return [
            'employee_id' => Employee::factory(),
            'date' => $date->toDateString(),
            'check_in_at' => $checkInAt,
            'check_out_at' => $checkOutAt,
            'status' => $status,
        ];
    }

    public function present(): static
    {
        return $this->state(function () {
            $date = Carbon::instance(fake()->dateTimeBetween('-1 week', 'now'))->startOfDay();
            $checkInAt = $date->copy()
                ->addHours(fake()->numberBetween(7, 10))
                ->addMinutes(fake()->randomElement([0, 15, 30, 45]));
            $checkOutAt = $checkInAt->copy()->addHours(fake()->numberBetween(7, 10));

            return [
                'date' => $date->toDateString(),
                'check_in_at' => $checkInAt,
                'check_out_at' => $checkOutAt,
                'status' => 'present',
            ];
        });
    }
}
