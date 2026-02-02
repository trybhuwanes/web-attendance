<?php

use App\Models\Attendance;
use Illuminate\Support\Carbon;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    private const TIMEZONE = 'Asia/Jakarta';

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
</section>
