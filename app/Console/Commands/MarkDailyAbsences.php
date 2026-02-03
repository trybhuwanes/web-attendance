<?php

namespace App\Console\Commands;

use App\Models\Attendance;
use App\Models\Employee;
use App\Models\Holiday;
use Illuminate\Console\Command;
use Illuminate\Support\Carbon;

class MarkDailyAbsences extends Command
{
    private const TIMEZONE = 'Asia/Jakarta';

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:mark-absent';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Mark active employees as absent after 16:00 when they have no attendance record';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $now = Carbon::now(self::TIMEZONE);
        $cutoff = $now->copy()->setTime(16, 0);

        if ($now->lt($cutoff)) {
            return self::SUCCESS;
        }

        $date = $now->toDateString();
        $timestamp = $now;

        if ($now->isWeekend() || Holiday::query()->whereDate('date', $date)->exists()) {
            return self::SUCCESS;
        }

        $employeeIds = Employee::query()
            ->where('is_active', true)
            ->whereDoesntHave('attendances', function ($query) use ($date) {
                $query->whereDate('date', $date);
            })
            ->pluck('id');

        if ($employeeIds->isEmpty()) {
            return self::SUCCESS;
        }

        $rows = $employeeIds->map(fn ($id) => [
            'employee_id' => $id,
            'date' => $date,
            'check_in_at' => null,
            'check_out_at' => null,
            'status' => 'absent',
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->all();

        Attendance::query()->insert($rows);

        return self::SUCCESS;
    }
}
