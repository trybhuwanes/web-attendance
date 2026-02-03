<?php

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Attributes\Computed;
use Livewire\Component;
use Livewire\WithPagination;

new class extends Component {
    use WithPagination;

    public string $status = 'pending';

    public function updatingStatus(): void
    {
        $this->resetPage();
    }

    public function approve(int $requestId): void
    {
        $request = AttendanceRequest::query()->with('employee')->findOrFail($requestId);

        if ($request->status !== 'pending') {
            return;
        }

        Attendance::query()->updateOrCreate(
            [
                'employee_id' => $request->employee_id,
                'date' => $request->date->toDateString(),
            ],
            [
                'check_in_at' => null,
                'check_out_at' => null,
                'status' => $request->type,
            ],
        );

        $request->update(['status' => 'approved']);
    }

    public function reject(int $requestId): void
    {
        $request = AttendanceRequest::query()->findOrFail($requestId);

        if ($request->status !== 'pending') {
            return;
        }

        $request->update(['status' => 'rejected']);
    }

    #[Computed]
    public function requests(): LengthAwarePaginator
    {
        return AttendanceRequest::query()
            ->with('employee')
            ->when($this->status !== '', fn ($query) => $query->where('status', $this->status))
            ->orderByDesc('date')
            ->paginate(15);
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div>
        <flux:heading size="lg">{{ __('Attendance Requests') }}</flux:heading>
        <flux:subheading>{{ __('Approve sick/leave requests from employees.') }}</flux:subheading>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field class="max-w-xs">
            <flux:label>{{ __('Status') }}</flux:label>
            <select
                wire:model.live="status"
                class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
            >
                <option value="">{{ __('All') }}</option>
                <option value="pending">{{ __('Pending') }}</option>
                <option value="approved">{{ __('Approved') }}</option>
                <option value="rejected">{{ __('Rejected') }}</option>
            </select>
        </flux:field>
    </div>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Employee') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                        <th class="px-4 py-3 text-right">{{ __('Actions') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->requests as $request)
                        <tr wire:key="request-{{ $request->id }}" class="hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $request->date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                <div class="font-medium text-zinc-900 dark:text-zinc-100">
                                    {{ $request->employee->name }}
                                </div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400">
                                    {{ $request->employee->employee_code }}
                                </div>
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ ucfirst($request->type) }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $request->status === 'approved' ? 'green' : ($request->status === 'rejected' ? 'red' : 'amber') }}">
                                    {{ ucfirst($request->status) }}
                                </flux:badge>
                            </td>
                            <td class="px-4 py-3">
                                <div class="flex justify-end gap-2">
                                    @if ($request->status === 'pending')
                                        <flux:button
                                            variant="primary"
                                            wire:click="approve({{ $request->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('Approve') }}
                                        </flux:button>
                                        <flux:button
                                            variant="ghost"
                                            wire:click="reject({{ $request->id }})"
                                            wire:loading.attr="disabled"
                                        >
                                            {{ __('Reject') }}
                                        </flux:button>
                                    @else
                                        <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                                            {{ __('No actions') }}
                                        </flux:text>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No requests found.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <div class="px-4 py-3">
            {{ $this->requests->links() }}
        </div>
    </div>
</section>
