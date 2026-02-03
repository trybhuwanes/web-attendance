<?php

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use App\Models\Employee;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    private const TIMEZONE = 'Asia/Jakarta';

    public int $adminMonth;
    public int $adminYear;

    public function mount(): void
    {
        $now = Carbon::now(self::TIMEZONE);
        $this->adminMonth = (int) $now->month;
        $this->adminYear = (int) $now->year;
    }

    public function updatedAdminMonth($value): void
    {
        $month = (int) $value;
        $this->adminMonth = max(1, min(12, $month));
    }

    public function updatedAdminYear($value): void
    {
        $year = (int) $value;
        $this->adminYear = max(2000, min(2100, $year));
    }

    #[Computed]
    public function todayStats(): array
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return [
                'present' => 0,
                'absent' => 0,
                'sick' => 0,
                'leave' => 0,
            ];
        }

        $date = Carbon::now(self::TIMEZONE)->toDateString();

        $row = Attendance::query()
            ->select([
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
            ])
            ->whereDate('date', $date)
            ->first();

        return [
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'sick' => (int) ($row->sick ?? 0),
            'leave' => (int) ($row->leave_days ?? 0),
        ];
    }

    #[Computed]
    public function pendingApprovals(): int
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return 0;
        }

        return AttendanceRequest::query()
            ->where('status', 'pending')
            ->count();
    }

    #[Computed]
    public function monthlySummary(): array
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return [
                'present' => 0,
                'absent' => 0,
                'sick' => 0,
                'leave' => 0,
            ];
        }

        $start = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)
            ->startOfMonth()
            ->toDateString();
        $end = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)
            ->endOfMonth()
            ->toDateString();

        $row = Attendance::query()
            ->select([
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
            ])
            ->whereBetween('date', [$start, $end])
            ->first();

        return [
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'sick' => (int) ($row->sick ?? 0),
            'leave' => (int) ($row->leave_days ?? 0),
        ];
    }

    #[Computed]
    public function topDepartments()
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return collect();
        }

        $start = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)
            ->startOfMonth()
            ->toDateString();
        $end = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)
            ->endOfMonth()
            ->toDateString();

        return Employee::query()
            ->leftJoin('attendances', function ($join) use ($start, $end) {
                $join->on('employees.id', '=', 'attendances.employee_id')
                    ->whereBetween('attendances.date', [$start, $end])
                    ->where('attendances.status', 'present');
            })
            ->select([
                'employees.department',
                DB::raw('COUNT(attendances.id) as present_days'),
            ])
            ->whereNotNull('employees.department')
            ->groupBy('employees.department')
            ->orderByDesc('present_days')
            ->limit(5)
            ->get();
    }

    #[Computed]
    public function weeklyTotals()
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'admin') {
            return collect();
        }

        $start = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)->startOfMonth();
        $end = Carbon::create($this->adminYear, $this->adminMonth, 1, 0, 0, 0, self::TIMEZONE)->endOfMonth();

        $attendances = Attendance::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get(['date', 'status']);

        $rows = $attendances
            ->groupBy(fn ($attendance) => Carbon::parse($attendance->date, self::TIMEZONE)->startOfWeek(Carbon::MONDAY)->format('o-W'))
            ->map(function ($group) {
                return [
                    'present' => $group->where('status', 'present')->count(),
                    'absent' => $group->where('status', 'absent')->count(),
                    'sick' => $group->where('status', 'sick')->count(),
                    'leave' => $group->where('status', 'leave')->count(),
                ];
            });

        $weeks = [];
        $cursor = $start->copy()->startOfWeek(Carbon::MONDAY);

        while ($cursor->lte($end)) {
            $weekKey = $cursor->format('o-W');
            $label = $cursor->format('M d');
            $row = $rows->get($weekKey);

            $weeks[] = [
                'label' => $label,
                'present' => (int) ($row['present'] ?? 0),
                'absent' => (int) ($row['absent'] ?? 0),
                'sick' => (int) ($row['sick'] ?? 0),
                'leave' => (int) ($row['leave'] ?? 0),
            ];

            $cursor->addWeek();
        }

        return $weeks;
    }

    #[Computed]
    public function employeeMonthlySummary(): array
    {
        $user = auth()->user();

        if (! $user || $user->role !== 'employee' || ! $user->employee) {
            return [
                'present' => 0,
                'absent' => 0,
                'sick' => 0,
                'leave' => 0,
            ];
        }

        $now = Carbon::now(self::TIMEZONE);
        $start = $now->copy()->startOfMonth()->toDateString();
        $end = $now->copy()->endOfMonth()->toDateString();

        $row = Attendance::query()
            ->select([
                DB::raw("SUM(CASE WHEN status = 'present' THEN 1 ELSE 0 END) as present"),
                DB::raw("SUM(CASE WHEN status = 'absent' THEN 1 ELSE 0 END) as absent"),
                DB::raw("SUM(CASE WHEN status = 'sick' THEN 1 ELSE 0 END) as sick"),
                DB::raw("SUM(CASE WHEN status = 'leave' THEN 1 ELSE 0 END) as leave_days"),
            ])
            ->where('employee_id', $user->employee->id)
            ->whereBetween('date', [$start, $end])
            ->first();

        return [
            'present' => (int) ($row->present ?? 0),
            'absent' => (int) ($row->absent ?? 0),
            'sick' => (int) ($row->sick ?? 0),
            'leave' => (int) ($row->leave_days ?? 0),
        ];
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    @if (auth()->user()?->isAdmin())
        <div>
            <flux:heading size="lg">{{ __('Admin Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Overview for today and this month.') }}</flux:subheading>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Month') }}</flux:label>
                <select
                    wire:model.live="adminMonth"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
                >
                    @foreach (range(1, 12) as $value)
                        <option value="{{ $value }}">{{ Carbon::create()->month($value)->format('F') }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Year') }}</flux:label>
                <flux:input wire:model.live="adminYear" type="number" min="2000" max="2100" />
            </flux:field>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-5">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today Present') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->todayStats['present'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today Absent') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->todayStats['absent'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today Sick') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->todayStats['sick'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Today Leave') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->todayStats['leave'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Pending Approvals') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->pendingApprovals }}</flux:heading>
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ __('Weekly Attendance Trend') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Totals per week for this month.') }}</flux:text>
                </div>
            </div>

            <div class="mt-6 grid gap-4">
                @php
                    $maxTotal = collect($this->weeklyTotals)->map(fn ($row) => $row['present'] + $row['absent'] + $row['sick'] + $row['leave'])->max() ?? 1;
                @endphp

                @forelse ($this->weeklyTotals as $week)
                    @php
                        $total = $week['present'] + $week['absent'] + $week['sick'] + $week['leave'];
                        $scale = $maxTotal > 0 ? ($total / $maxTotal) * 100 : 0;
                        $presentWidth = $total > 0 ? ($week['present'] / $total) * 100 : 0;
                        $absentWidth = $total > 0 ? ($week['absent'] / $total) * 100 : 0;
                        $sickWidth = $total > 0 ? ($week['sick'] / $total) * 100 : 0;
                        $leaveWidth = $total > 0 ? ($week['leave'] / $total) * 100 : 0;
                    @endphp

                    <div class="grid gap-2">
                        <div class="flex items-center justify-between text-xs text-zinc-500 dark:text-zinc-400">
                            <span>{{ $week['label'] }}</span>
                            <span>{{ $total }}</span>
                        </div>
                        <div class="h-4 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                            <div class="flex h-4 overflow-hidden rounded-full" style="width: {{ $scale }}%">
                                <span class="h-full bg-emerald-500" style="width: {{ $presentWidth }}%"></span>
                                <span class="h-full bg-amber-500" style="width: {{ $absentWidth }}%"></span>
                                <span class="h-full bg-sky-500" style="width: {{ $sickWidth }}%"></span>
                                <span class="h-full bg-violet-500" style="width: {{ $leaveWidth }}%"></span>
                            </div>
                        </div>
                        <div class="flex flex-wrap gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-emerald-500"></span>{{ __('Present') }} {{ $week['present'] }}</span>
                            <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-amber-500"></span>{{ __('Absent') }} {{ $week['absent'] }}</span>
                            <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-sky-500"></span>{{ __('Sick') }} {{ $week['sick'] }}</span>
                            <span class="flex items-center gap-1"><span class="inline-block h-2 w-2 rounded-full bg-violet-500"></span>{{ __('Leave') }} {{ $week['leave'] }}</span>
                        </div>
                    </div>
                @empty
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                        {{ __('No weekly data yet.') }}
                    </flux:text>
                @endforelse
            </div>
        </div>

        <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
            <div class="flex items-center justify-between gap-3">
                <div>
                    <flux:heading size="sm">{{ __('Top Departments') }}</flux:heading>
                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('By present days this month.') }}</flux:text>
                </div>
            </div>

            <div class="mt-4 overflow-x-auto">
                <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                    <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                        <tr>
                            <th class="px-4 py-3">{{ __('Department') }}</th>
                            <th class="px-4 py-3 text-right">{{ __('Present Days') }}</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                        @forelse ($this->topDepartments as $department)
                            <tr wire:key="dept-{{ $department->department }}">
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $department->department }}</td>
                                <td class="px-4 py-3 text-right text-zinc-700 dark:text-zinc-300">{{ $department->present_days }}</td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="2" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                    {{ __('No department data yet.') }}
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div>
            <flux:heading size="lg">{{ __('My Dashboard') }}</flux:heading>
            <flux:subheading>{{ __('Your attendance totals for this month.') }}</flux:subheading>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Present') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->employeeMonthlySummary['present'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Absent') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->employeeMonthlySummary['absent'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Sick') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->employeeMonthlySummary['sick'] }}</flux:heading>
            </div>
            <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Leave') }}</flux:text>
                <flux:heading size="lg" class="mt-2">{{ $this->employeeMonthlySummary['leave'] }}</flux:heading>
            </div>
        </div>
    @endif
</section>
