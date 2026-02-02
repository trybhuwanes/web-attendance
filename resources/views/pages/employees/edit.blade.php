<?php

use App\Models\Employee;
use Illuminate\Validation\Rule;
use Livewire\Component;

new class extends Component {
    public Employee $employee;
    public string $employee_code = '';
    public string $name = '';
    public string $email = '';
    public string $department = '';
    public string $position = '';
    public bool $is_active = true;

    public function mount(Employee $employee): void
    {
        $this->employee = $employee;
        $this->employee_code = $employee->employee_code;
        $this->name = $employee->name;
        $this->email = $employee->email;
        $this->department = $employee->department;
        $this->position = $employee->position;
        $this->is_active = $employee->is_active;
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employee_code' => [
                'required',
                'string',
                'max:255',
                Rule::unique('employees', 'employee_code')->ignore($this->employee->id),
            ],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'department' => ['required', 'string', 'max:255'],
            'position' => ['required', 'string', 'max:255'],
            'is_active' => ['boolean'],
        ];
    }

    public function save(): void
    {
        $validated = $this->validate();

        $this->employee->update($validated);

        session()->flash('status', __('Employee updated.'));

        $this->redirectRoute('employees.index', navigate: true);
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Edit Employee') }}</flux:heading>
            <flux:subheading>{{ __('Update employee details.') }}</flux:subheading>
        </div>

        <flux:button variant="ghost" :href="route('employees.index')" wire:navigate>
            {{ __('Back to list') }}
        </flux:button>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Employee Code') }}</flux:label>
                <flux:input wire:model.defer="employee_code" type="text" required />
                <flux:error name="employee_code" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model.defer="name" type="text" required />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input wire:model.defer="email" type="email" required />
                <flux:error name="email" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Department') }}</flux:label>
                <flux:input wire:model.defer="department" type="text" required />
                <flux:error name="department" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Position') }}</flux:label>
                <flux:input wire:model.defer="position" type="text" required />
                <flux:error name="position" />
            </flux:field>
        </div>

        <flux:checkbox wire:model.defer="is_active" :label="__('Active')" />

        <div class="flex items-center justify-end gap-3">
            <flux:button variant="ghost" :href="route('employees.index')" wire:navigate>
                {{ __('Cancel') }}
            </flux:button>
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                {{ __('Save Changes') }}
            </flux:button>
        </div>
    </form>
</section>
