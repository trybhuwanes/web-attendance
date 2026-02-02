<?php

use App\Models\Attendance;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public string $status = '';
    public string $dateFrom = '';
    public string $dateTo = '';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function updatingDateFrom(): void
    {
        $this->resetPage();
    }

    public function updatingDateTo(): void
    {
        $this->resetPage();
    }

    #[Computed]
    public function attendances(): LengthAwarePaginator
    {
        return Attendance::query()
            ->with('employee')
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';

                $query->whereHas('employee', function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('employee_code', 'like', $search)
                        ->orWhere('email', 'like', $search);
                });
            })
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->when($this->dateFrom !== '', fn ($query) => $query->whereDate('date', '>=', $this->dateFrom))
            ->when($this->dateTo !== '', fn ($query) => $query->whereDate('date', '<=', $this->dateTo))
            ->orderByDesc('date')
            ->orderByDesc('check_in_at')
            ->paginate($this->perPage)
            ->withQueryString();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="lg">{{ __('Attendance Report') }}</flux:heading>
        <flux:subheading>{{ __('Review daily attendance across employees.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 lg:grid-cols-4">
            <flux:input
                wire:model.live.debounce.300ms="search"
                :label="__('Search')"
                :placeholder="__('Name, code, email')"
            />

            <flux:input
                wire:model.live="status"
                :label="__('Status')"
                placeholder="present / sick / leave / absent"
            />

            <flux:input wire:model.live="dateFrom" :label="__('From Date')" type="date" />
            <flux:input wire:model.live="dateTo" :label="__('To Date')" type="date" />
        </div>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3">{{ __('Check In') }}</th>
                        <th class="px-4 py-3">{{ __('Check Out') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->attendances as $attendance)
                        <tr wire:key="attendance-{{ $attendance->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $attendance->date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $attendance->employee->name }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $attendance->employee->employee_code }}
                                </div>
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
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No attendance records found.') }}
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
