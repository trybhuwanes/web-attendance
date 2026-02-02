<?php

namespace Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Employee>
 */
class EmployeeFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'employee_code' => fake()->unique()->bothify('EMP-####'),
            'name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'department' => fake()->randomElement(['Engineering', 'HR', 'Finance', 'Operations', 'Sales']),
            'position' => fake()->jobTitle(),
            'is_active' => fake()->boolean(90),
        ];
    }
}
