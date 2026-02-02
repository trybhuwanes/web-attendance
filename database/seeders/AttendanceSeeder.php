<?php

namespace Database\Seeders;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Database\Seeder;
use Illuminate\Support\Carbon;

class AttendanceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $employees = Employee::query()
            ->orderBy('id')
            ->limit(10)
            ->get();

        if ($employees->isEmpty()) {
            return;
        }

        $start = Carbon::create(2026, 1, 1, 0, 0, 0, 'Asia/Jakarta');
        $end = Carbon::create(2026, 1, 30, 0, 0, 0, 'Asia/Jakarta');

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date, 'Asia/Jakarta')->toDateString())
            ->unique()
            ->toArray();

        $current = $start->copy();

        while ($current->lte($end)) {
            $currentDate = $current->toDateString();

            if ($current->isWeekend() || in_array($currentDate, $holidayDates, true)) {
                $current->addDay();

                continue;
            }

            foreach ($employees as $employee) {
                $status = $this->randomStatus();

                $checkInAt = null;
                $checkOutAt = null;

                if ($status === 'present') {
                    $checkInAt = $current->copy()
                        ->addHours(fake()->numberBetween(7, 10))
                        ->addMinutes(fake()->randomElement([0, 15, 30, 45]));
                    $checkOutAt = $checkInAt->copy()->addHours(fake()->numberBetween(7, 10));
                }

                Attendance::query()->firstOrCreate(
                    [
                        'employee_id' => $employee->id,
                        'date' => $currentDate,
                    ],
                    [
                        'check_in_at' => $checkInAt,
                        'check_out_at' => $checkOutAt,
                        'status' => $status,
                    ],
                );
            }

            $current->addDay();
        }
    }

    private function randomStatus(): string
    {
        $roll = fake()->numberBetween(1, 100);

        if ($roll <= 75) {
            return 'present';
        }

        if ($roll <= 85) {
            return 'sick';
        }

        if ($roll <= 90) {
            return 'leave';
        }

        return 'absent';
    }
}
