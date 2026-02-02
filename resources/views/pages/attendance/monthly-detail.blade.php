<?php

use App\Models\Attendance;
use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\Attributes\Url;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    private const TIMEZONE = 'Asia/Jakarta';

    public Employee $employee;

    #[Url]
    public int $month;

    #[Url]
    public int $year;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
        $now = Carbon::now(self::TIMEZONE);
        $this->month = (int) ($this->month ?: $now->month);
        $this->year = (int) ($this->year ?: $now->year);

        $this->updatedMonth($this->month);
        $this->updatedYear($this->year);
    }

    public function updatedMonth($value): void
    {
        $month = (int) $value;
        $this->month = max(1, min(12, $month));
        $this->resetPage();
    }

    public function updatedYear($value): void
    {
        $year = (int) $value;
        $this->year = max(2000, min(2100, $year));
        $this->resetPage();
    }

    #[Computed]
    public function attendances(): LengthAwarePaginator
    {
        $start = $this->startDate()->toDateString();
        $end = $this->endDate()->toDateString();

        return Attendance::query()
            ->where('employee_id', $this->employee->id)
            ->whereBetween('date', [$start, $end])
            ->orderBy('date')
            ->paginate(15);
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
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Monthly Attendance Detail') }}</flux:heading>
            <flux:subheading>
                {{ $employee->name }} — {{ \Illuminate\Support\Carbon::create($this->year, $this->month, 1)->format('F Y') }}
            </flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('attendance.report.monthly')" wire:navigate>
            {{ __('Back to report') }}
        </flux:button>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
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
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
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
                    @forelse ($this->attendances as $attendance)
                        <tr wire:key="attendance-detail-{{ $attendance->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $attendance->date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $attendance->status === 'present' ? 'green' : 'gray' }}">
                                    {{ ucfirst($attendance->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $attendance->check_in_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $attendance->check_out_at?->timezone('Asia/Jakarta')->format('H:i') ?? __('—') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No attendance records found for this month.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $this->attendances->links() }}
        </div>
    </div>
</section>
