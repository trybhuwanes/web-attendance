<?php

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    private const TIMEZONE = 'Asia/Jakarta';

    public int $month;
    public int $year;
    public int $perPage = 10;

    public function mount(): void
    {
        $now = Carbon::now(self::TIMEZONE);
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function updatingMonth(): void
    {
        $this->resetPage();
    }

    public function updatingYear(): void
    {
        $this->resetPage();
    }

    public function updatedMonth($value): void
    {
        $month = (int) $value;
        $this->month = max(1, min(12, $month));
    }

    public function updatedYear($value): void
    {
        $year = (int) $value;
        $this->year = max(2000, min(2100, $year));
    }

    #[Computed]
    public function workingDays(): int
    {
        $start = $this->startDate();
        $end = $this->endDate();

        $workingDays = 0;
        $current = $start->copy();

        while ($current->lte($end)) {
            if (! $current->isWeekend()) {
                $workingDays++;
            }

            $current->addDay();
        }

        $holidayCount = (int) DB::table('holidays')
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->distinct()
            ->count('date');

        return max($workingDays - $holidayCount, 0);
    }

    #[Computed]
    public function reportRows(): LengthAwarePaginator
    {
        $start = $this->startDate()->toDateString();
        $end = $this->endDate()->toDateString();

        $attendanceSubquery = DB::table('attendances')
            ->select([
                'employee_id',
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present_days"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent_days"),
                DB::raw("SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick_days"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
            ])
            ->whereBetween('date', [$start, $end])
            ->groupBy('employee_id');

        return Employee::query()
            ->leftJoinSub($attendanceSubquery, 'attendance_totals', function ($join) {
                $join->on('employees.id', '=', 'attendance_totals.employee_id');
            })
            ->select([
                'employees.id',
                'employees.name',
                DB::raw('COALESCE(attendance_totals.present_days, 0) as present_days'),
                DB::raw('COALESCE(attendance_totals.absent_days, 0) as absent_days'),
                DB::raw('COALESCE(attendance_totals.sick_days, 0) as sick_days'),
                DB::raw('COALESCE(attendance_totals.leave_days, 0) as leave_days'),
            ])
            ->orderBy('employees.name')
            ->paginate($this->perPage);
    }

    private function startDate(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1, 0, 0, 0, self::TIMEZONE)->startOfMonth();
    }

    private function endDate(): Carbon
    {
        return Carbon::create($this->year, $this->month, 1, 0, 0, 0, self::TIMEZONE)->endOfMonth();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="lg">{{ __('Monthly Attendance Report') }}</flux:heading>
        <flux:subheading>
            {{ __('Summary per employee for :monthYear.', ['monthYear' => \Illuminate\Support\Carbon::create($this->year, $this->month, 1)->format('F Y')]) }}
        </flux:subheading>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-4">
            <flux:field>
                <flux:label>{{ __('Month') }}</flux:label>
                <select
                    wire:model.live="month"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
                >
                    @foreach (range(1, 12) as $value)
                        <option value="{{ $value }}">
                            {{ Carbon::create()->month($value)->format('F') }}
                        </option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Year') }}</flux:label>
                <flux:input wire:model.live="year" type="number" min="2000" max="2100" />
            </flux:field>

            <div class="rounded-lg border border-zinc-200 p-4 text-sm dark:border-zinc-700">
                <div class="text-zinc-500 dark:text-zinc-400">{{ __('Working Days') }}</div>
                <div class="mt-2 text-lg font-semibold text-zinc-900 dark:text-zinc-100">
                    {{ $this->workingDays }}
                </div>
            </div>
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Working Days') }}</th>
                        <th class="px-4 py-3">{{ __('Present') }}</th>
                        <th class="px-4 py-3">{{ __('Absent') }}</th>
                        <th class="px-4 py-3">{{ __('Sick') }}</th>
                        <th class="px-4 py-3">{{ __('Leave') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->reportRows as $row)
                        <tr wire:key="monthly-attendance-{{ $row->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3">
                                <flux:link
                                    :href="route('attendance.report.monthly.detail', [
                                        'employee' => $row->id,
                                        'month' => $this->month,
                                        'year' => $this->year,
                                    ])"
                                    wire:navigate
                                >
                                    {{ $row->name }}
                                </flux:link>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $this->workingDays }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->present_days }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->absent_days }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->sick_days }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $row->leave_days }}</td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="6" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No employees found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $this->reportRows->links() }}
        </div>
    </div>
</section>
