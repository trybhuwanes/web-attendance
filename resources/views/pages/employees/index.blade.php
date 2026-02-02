<?php

use App\Models\Employee;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $search = '';
    public int $perPage = 10;

    public function updatingSearch(): void
    {
        $this->resetPage();
    }

    public function toggleActive(int $employeeId): void
    {
        $employee = Employee::query()->findOrFail($employeeId);

        $employee->update([
            'is_active' => ! $employee->is_active,
        ]);
    }

    #[Computed]
    public function employees(): LengthAwarePaginator
    {
        return Employee::query()
            ->when($this->search !== '', function ($query) {
                $search = '%'.$this->search.'%';

                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', $search)
                        ->orWhere('employee_code', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('department', 'like', $search)
                        ->orWhere('position', 'like', $search);
                });
            })
            ->orderBy('name')
            ->paginate($this->perPage)
            ->withQueryString();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Employees') }}</flux:heading>
            <flux:subheading>{{ __('Manage employee master data.') }}</flux:subheading>
        </div>

        <flux:button variant="primary" :href="route('employees.create')" wire:navigate>
            {{ __('Add Employee') }}
        </flux:button>
    </div>

    <div class="flex flex-wrap items-end gap-4">
        <flux:input
            wire:model.live.debounce.300ms="search"
            :label="__('Search')"
            :placeholder="__('Name, code, email, department, position')"
            class="w-full max-w-md"
        />
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Code') }}</th>
                        <th class="px-4 py-3">{{ __('Name') }}</th>
                        <th class="px-4 py-3">{{ __('Email') }}</th>
                        <th class="px-4 py-3">{{ __('Department') }}</th>
                        <th class="px-4 py-3">{{ __('Position') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->employees as $employee)
                        <tr wire:key="employee-{{ $employee->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3 font-medium text-zinc-900 dark:text-zinc-100">
                                {{ $employee->employee_code }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->name }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->email }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->department }}</td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">{{ $employee->position }}</td>
                            <td class="px-4 py-3">
                                @if ($employee->is_active)
                                    <flux:badge color="green">{{ __('Active') }}</flux:badge>
                                @else
                                    <flux:badge color="red">{{ __('Inactive') }}</flux:badge>
                                @endif
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    <flux:button
                                        variant="ghost"
                                        :href="route('employees.edit', $employee)"
                                        wire:navigate
                                    >
                                        {{ __('Edit') }}
                                    </flux:button>
                                    <flux:button
                                        variant="ghost"
                                        wire:click="toggleActive({{ $employee->id }})"
                                        wire:loading.attr="disabled"
                                    >
                                        {{ $employee->is_active ? __('Deactivate') : __('Activate') }}
                                    </flux:button>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No employees found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $this->employees->links() }}
        </div>
    </div>
</section>
