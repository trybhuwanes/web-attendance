<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Computed;
use Livewire\Component;

new class extends Component {
    public int $employee_id = 0;
    public string $name = '';
    public string $email = '';
    public string $password = '';

    #[Computed]
    public function employees()
    {
        return Employee::query()
            ->whereDoesntHave('user')
            ->orderBy('name')
            ->get();
    }

    /**
     * @return array<string, mixed>
     */
    protected function rules(): array
    {
        return [
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id'),
                Rule::unique('users', 'employee_id'),
            ],
            'password' => ['required', 'string', 'min:8'],
        ];
    }

    public function updatedEmployeeId(): void
    {
        $employeeId = (int) $this->employee_id;

        if ($employeeId === 0) {
            $this->name = '';
            $this->email = '';

            return;
        }

        $employee = Employee::query()->find($employeeId);

        if (! $employee) {
            $this->name = '';
            $this->email = '';

            return;
        }

        $this->name = $employee->name;
        $this->email = $employee->email;
    }

    public function save(): void
    {
        $validated = $this->validate();
        $employee = Employee::query()->findOrFail($validated['employee_id']);

        if ($employee->name === '' || $employee->email === '') {
            $this->addError('employee_id', __('Selected employee is missing name or email.'));

            return;
        }

        User::create([
            'employee_id' => $employee->id,
            'name' => $employee->name,
            'email' => $employee->email,
            'password' => Hash::make($validated['password']),
            'role' => 'employee',
        ]);

        session()->flash('status', __('Employee user created.'));

        $this->reset(['employee_id', 'name', 'email', 'password']);

        $this->redirectRoute('users.create', navigate: true);
    }
}; ?>

<section class="flex w-full flex-col gap-6">
    <div class="flex flex-wrap items-center justify-between gap-4">
        <div>
            <flux:heading size="lg">{{ __('Create Employee User') }}</flux:heading>
            <flux:subheading>{{ __('Link a user account to an employee record.') }}</flux:subheading>
        </div>
    </div>

    @if (session('status'))
        <flux:callout variant="success">
            {{ session('status') }}
        </flux:callout>
    @endif

    <form wire:submit="save" class="grid gap-6 rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-700 dark:bg-zinc-900">
        <flux:field>
            <flux:label>{{ __('Employee') }}</flux:label>
            <flux:select wire:model.live="employee_id" required>
                <option value="0">{{ __('Select employee') }}</option>
                @foreach ($this->employees as $employee)
                    <option value="{{ $employee->id }}">
                        {{ $employee->employee_code }} - {{ $employee->name }}
                    </option>
                @endforeach
            </flux:select>
            <flux:error name="employee_id" />
        </flux:field>

        <div class="grid gap-4 md:grid-cols-2">
            <flux:field>
                <flux:label>{{ __('Name') }}</flux:label>
                <flux:input wire:model="name" type="text" disabled />
                <flux:error name="name" />
            </flux:field>

            <flux:field>
                <flux:label>{{ __('Email') }}</flux:label>
                <flux:input wire:model="email" type="email" disabled />
                <flux:error name="email" />
            </flux:field>
        </div>

        <flux:field>
            <flux:label>{{ __('Password') }}</flux:label>
            <flux:input wire:model.defer="password" type="password" required />
            <flux:error name="password" />
        </flux:field>

        <div class="flex items-center justify-end gap-3">
            <flux:button variant="primary" type="submit" wire:loading.attr="disabled">
                {{ __('Create User') }}
            </flux:button>
        </div>
    </form>
</section>
