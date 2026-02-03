<?php

use App\Models\Attendance;
use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    private const TIMEZONE = 'Asia/Jakarta';

    public int $month;
    public int $year;

    public function mount(): void
    {
        $now = Carbon::now(self::TIMEZONE);
        $this->month = (int) $now->month;
        $this->year = (int) $now->year;
    }

    public function checkIn(): void
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            abort(403);
        }

        $today = $this->todayDate();
        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($attendance?->check_in_at) {
            $this->addError('attendance', __('You have already checked in today.'));

            return;
        }

        $now = Carbon::now(self::TIMEZONE);

        if ($attendance) {
            $attendance->update([
                'check_in_at' => $now,
                'check_out_at' => null,
                'status' => 'present',
            ]);
        } else {
            Attendance::create([
                'employee_id' => $employee->id,
                'date' => $today,
                'check_in_at' => $now,
                'check_out_at' => null,
                'status' => 'present',
            ]);
        }

        $this->resetErrorBag('attendance');
    }

    public function checkOut(): void
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            abort(403);
        }

        $attendance = $this->todayAttendance();

        if (! $attendance || ! $attendance->check_in_at) {
            $this->addError('attendance', __('You need to check in before checking out.'));

            return;
        }

        if ($attendance->check_out_at) {
            $this->addError('attendance', __('You have already checked out today.'));

            return;
        }

        $attendance->update([
            'check_out_at' => Carbon::now(self::TIMEZONE),
        ]);

        $this->resetErrorBag('attendance');
    }

    #[Computed]
    public function todayAttendance(): ?Attendance
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return null;
        }

        return Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $this->todayDate())
            ->first();
    }

    private function todayDate(): string
    {
        return Carbon::now(self::TIMEZONE)->toDateString();
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
    public function monthlyRows()
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return [];
        }

        $start = Carbon::create($this->year, $this->month, 1, 0, 0, 0, self::TIMEZONE)->startOfMonth();
        $end = $start->copy()->endOfMonth();
        $today = Carbon::now(self::TIMEZONE)->toDateString();

        $holidayDates = Holiday::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->pluck('date')
            ->map(fn ($date) => Carbon::parse($date, self::TIMEZONE)->toDateString())
            ->unique()
            ->toArray();

        $attendanceByDate = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->keyBy(fn ($attendance) => $attendance->date->toDateString());

        $rows = [];
        $current = $start->copy();

        while ($current->lte($end)) {
            $date = $current->toDateString();
            $isWeekend = $current->isWeekend();
            $isHoliday = in_array($date, $holidayDates, true);

            $rows[] = [
                'date' => $current->copy(),
                'attendance' => $attendanceByDate->get($date),
                'is_future' => $date > $today,
                'is_weekend' => $isWeekend,
                'is_holiday' => $isHoliday,
            ];

            $current->addDay();
        }

        return $rows;
    }

}; ?>

<section class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="lg">{{ __('Daily Attendance') }}</flux:heading>
        <flux:subheading>
            {{ __('Today: :date (Asia/Jakarta)', ['date' => now('Asia/Jakarta')->format('d M Y')]) }}
        </flux:subheading>
    </div>

    @error('attendance')
        <flux:callout variant="danger" icon="x-circle" heading="{{ $message }}" />
    @enderror

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Status') }}</flux:text>
                <div class="mt-2">
                    @if ($this->todayAttendance)
                        @if ($this->todayAttendance->check_out_at)
                            <flux:badge color="green">{{ __('Checked Out') }}</flux:badge>
                        @elseif ($this->todayAttendance->check_in_at)
                            <flux:badge color="green">{{ __('Checked In') }}</flux:badge>
                        @else
                            <flux:badge color="amber">{{ __('Pending') }}</flux:badge>
                        @endif
                    @else
                        <flux:badge color="gray">{{ __('Not Checked In') }}</flux:badge>
                    @endif
                </div>
            </div>

            <div class="flex flex-wrap gap-3">
                <flux:button variant="primary" wire:click="checkIn" wire:loading.attr="disabled">
                    {{ __('Check In') }}
                </flux:button>
                <flux:button variant="ghost" wire:click="checkOut" wire:loading.attr="disabled">
                    {{ __('Check Out') }}
                </flux:button>
            </div>
        </div>

        <div class="grid gap-4 sm:grid-cols-2">
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Check In At') }}</flux:text>
                <flux:heading size="sm" class="mt-2">
                    {{ $this->todayAttendance?->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                </flux:heading>
            </div>
            <div class="rounded-lg border border-zinc-200 p-4 dark:border-zinc-700">
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">{{ __('Check Out At') }}</flux:text>
                <flux:heading size="sm" class="mt-2">
                    {{ $this->todayAttendance?->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                </flux:heading>
            </div>
        </div>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="flex flex-wrap items-center justify-between gap-4">
            <div>
                <flux:heading size="sm">{{ __('Monthly Attendance') }}</flux:heading>
                <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                    {{ \Illuminate\Support\Carbon::create($this->year, $this->month, 1)->format('F Y') }}
                </flux:text>
            </div>
        </div>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Month') }}</flux:label>
                <select
                    wire:model.live="month"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
                >
                    @foreach (range(1, 12) as $value)
                        <option value="{{ $value }}">{{ \Illuminate\Support\Carbon::create()->month($value)->format('F') }}</option>
                    @endforeach
                </select>
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Year') }}</flux:label>
                <flux:input wire:model.live="year" type="number" min="2000" max="2100" />
            </flux:field>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Check In') }}</th>
                        <th class="px-4 py-3">{{ __('Check Out') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->monthlyRows as $row)
                        @php
                            $attendance = $row['attendance'];
                            $isFuture = $row['is_future'];
                            $isWeekend = $row['is_weekend'];
                            $isHoliday = $row['is_holiday'];
                        @endphp
                        <tr
                            wire:key="employee-monthly-{{ $row['date']->toDateString() }}"
                            class="{{ $isFuture ? 'bg-zinc-50 text-zinc-400 dark:bg-zinc-800/40 dark:text-zinc-500' : 'hover:bg-zinc-50 dark:hover:bg-zinc-800/60' }}"
                        >
                            <td class="px-4 py-3">
                                <span class="{{ ($isWeekend || $isHoliday) ? 'text-red-600 dark:text-red-400 font-medium' : '' }}">
                                    {{ $row['date']->format('d M Y') }}
                                </span>
                                @if ($isHoliday)
                                    <span class="ms-2 text-xs text-red-500 dark:text-red-400">{{ __('Holiday') }}</span>
                                @elseif ($isWeekend)
                                    <span class="ms-2 text-xs text-red-500 dark:text-red-400">{{ __('Weekend') }}</span>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                @if ($attendance)
                                    <flux:badge color="{{ $attendance->status === 'present' ? 'green' : 'gray' }}">
                                        {{ ucfirst($attendance->status) }}
                                    </flux:badge>
                                @else
                                    <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                        {{ $isFuture ? __('Not started') : __('No record') }}
                                    </flux:text>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                {{ $attendance?->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                            </td>
                            <td class="px-4 py-3">
                                {{ $attendance?->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No attendance data for this month.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
