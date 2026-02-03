<?php

use App\Models\Attendance;
use App\Models\AttendanceRequest;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    private const TIMEZONE = 'Asia/Jakarta';

    public string $requestDate = '';
    public string $requestType = '';

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

    #[Computed]
    public function requestHistory()
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            return collect();
        }

        return AttendanceRequest::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->limit(10)
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function requestRules(): array
    {
        return [
            'requestDate' => ['required', 'date'],
            'requestType' => ['required', 'in:sick,leave'],
        ];
    }

    public function submitRequest(): void
    {
        $employee = auth()->user()?->employee;

        if (! $employee) {
            abort(403);
        }

        $validated = $this->validate($this->requestRules());

        $date = Carbon::parse($validated['requestDate'], self::TIMEZONE)->toDateString();

        $exists = AttendanceRequest::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $date)
            ->exists();

        if ($exists) {
            $this->addError('requestDate', __('You already submitted a request for this date.'));

            return;
        }

        AttendanceRequest::create([
            'employee_id' => $employee->id,
            'date' => $date,
            'type' => $validated['requestType'],
            'status' => 'pending',
        ]);

        $this->reset(['requestDate', 'requestType']);
        $this->resetErrorBag(['requestDate', 'requestType']);

        session()->flash('request_status', __('Request submitted.'));
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
        <div>
            <flux:heading size="sm">{{ __('Request Sick/Leave') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Submit a request for a single date. Admin approval is required.') }}
            </flux:text>
        </div>

        @if (session('request_status'))
            <flux:callout variant="success">
                {{ session('request_status') }}
            </flux:callout>
        @endif

        <form wire:submit="submitRequest" class="grid gap-4 md:grid-cols-3">
            <flux:field>
                <flux:label>{{ __('Date') }}</flux:label>
                <flux:input wire:model.defer="requestDate" type="date" required />
                <flux:error name="requestDate" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Type') }}</flux:label>
                <select
                    wire:model.defer="requestType"
                    class="w-full rounded-lg border border-zinc-300 bg-white px-3 py-2 text-sm text-zinc-900 shadow-sm focus:border-zinc-900 focus:outline-none focus:ring-2 focus:ring-zinc-900/10 dark:border-zinc-700 dark:bg-zinc-900 dark:text-zinc-100 dark:focus:border-zinc-100 dark:focus:ring-zinc-100/10"
                    required
                >
                    <option value="">{{ __('Select type') }}</option>
                    <option value="sick">{{ __('Sick') }}</option>
                    <option value="leave">{{ __('Leave') }}</option>
                </select>
                <flux:error name="requestType" />
            </flux:field>

            <div class="flex items-end">
                <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                    {{ __('Submit Request') }}
                </flux:button>
            </div>
        </form>
    </div>

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div>
            <flux:heading size="sm">{{ __('Request History') }}</flux:heading>
            <flux:text class="text-sm text-zinc-500 dark:text-zinc-400">
                {{ __('Your latest sick/leave submissions.') }}
            </flux:text>
        </div>

        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Type') }}</th>
                        <th class="px-4 py-3">{{ __('Status') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->requestHistory as $request)
                        <tr wire:key="request-history-{{ $request->id }}">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $request->date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ ucfirst($request->type) }}
                            </td>
                            <td class="px-4 py-3">
                                <flux:badge color="{{ $request->status === 'approved' ? 'green' : ($request->status === 'rejected' ? 'red' : 'amber') }}">
                                    {{ ucfirst($request->status) }}
                                </flux:badge>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="3" class="px-4 py-8 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No requests yet.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
