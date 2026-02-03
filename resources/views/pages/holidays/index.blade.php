<?php

use App\Models\Holiday;
use Illuminate\Support\Carbon;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public string $date = '';
    public string $description = '';
    public int $year;

    public function mount(): void
    {
        $this->year = (int) Carbon::now('Asia/Jakarta')->year;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'date' => ['required', 'date', Rule::unique('holidays', 'date')],
            'description' => ['required', 'string', 'max:255'],
            'year' => ['required', 'integer', 'min:2000', 'max:2100'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        Holiday::create([
            'date' => $validated['date'],
            'description' => $validated['description'],
        ]);

        session()->flash('status', __('Holiday added.'));

        $this->reset(['date', 'description']);
    }

    #[Computed]
    public function holidays()
    {
        return Holiday::query()
            ->whereYear('date', $this->year)
            ->orderBy('date')
            ->get();
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Holidays') }}</flux:heading>
            <flux:subheading>{{ __('Manage holidays for the selected year.') }}</flux:subheading>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    <div class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field class="max-w-xs">
            <flux:label>{{ __('Year') }}</flux:label>
            <flux:input wire:model.live="year" type="number" min="2000" max="2100" />
            <flux:error name="year" />
        </flux:field>
    </div>

    <form wire:submit="save" class="grid gap-4 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900 md:grid-cols-3">
        <flux:field>
            <flux:label>{{ __('Date') }}</flux:label>
            <flux:input wire:model.defer="date" type="date" required />
            <flux:error name="date" />
        </flux:field>

        <flux:field class="md:col-span-2">
            <flux:label>{{ __('Description') }}</flux:label>
            <flux:input wire:model.defer="description" type="text" required />
            <flux:error name="description" />
        </flux:field>

        <div class="flex items-end">
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                {{ __('Add Holiday') }}
            </flux:button>
        </div>
    </form>

    <div class="overflow-hidden rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-zinc-200 text-sm dark:divide-zinc-700">
                <thead class="bg-zinc-50 text-left text-xs font-semibold uppercase tracking-wide text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
                    <tr>
                        <th class="px-4 py-3">{{ __('Date') }}</th>
                        <th class="px-4 py-3">{{ __('Description') }}</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-zinc-200 dark:divide-zinc-800">
                    @forelse ($this->holidays as $holiday)
                        <tr wire:key="holiday-{{ $holiday->id }}">
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $holiday->date->format('d M Y') }}
                            </td>
                            <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300">
                                {{ $holiday->description }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="2" class="px-4 py-10 text-center text-sm text-zinc-500 dark:text-zinc-400">
                                {{ __('No holidays found for this year.') }}
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</section>
