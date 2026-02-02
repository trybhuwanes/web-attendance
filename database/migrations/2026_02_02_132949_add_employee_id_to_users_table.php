<?php

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (! Schema::hasColumn('users', 'employee_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->unsignedBigInteger('employee_id')->nullable()->after('id');
            });
        }

        if (Schema::hasColumn('users', 'employee_id') && DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `users` MODIFY `employee_id` BIGINT UNSIGNED NULL');
        }

        $invalidEmployeeIds = DB::table('users')
            ->whereNotNull('employee_id')
            ->whereNotIn('employee_id', function ($query) {
                $query->select('id')->from('employees');
            })
            ->pluck('id');

        if ($invalidEmployeeIds->isNotEmpty()) {
            DB::table('users')
                ->whereIn('id', $invalidEmployeeIds)
                ->update(['employee_id' => null]);
        }

        $users = User::query()->whereNull('employee_id')->get();

        if ($users->isNotEmpty()) {
            $nextNumber = (int) (Employee::query()->max('id') ?? 0);

            foreach ($users as $user) {
                do {
                    $nextNumber++;
                    $employeeCode = sprintf('EMP-%06d', $nextNumber);
                } while (Employee::query()->where('employee_code', $employeeCode)->exists());

                $employee = Employee::create([
                    'employee_code' => $employeeCode,
                    'name' => $user->name ?? 'Employee',
                    'email' => $user->email ?? sprintf('employee-%s@example.com', $employeeCode),
                    'department' => 'General',
                    'position' => 'Staff',
                    'is_active' => true,
                ]);

                $user->update([
                    'employee_id' => $employee->id,
                ]);
            }
        }

        if (Schema::hasColumn('users', 'employee_id')) {
            if (! $this->indexExists('users', 'users_employee_id_unique')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->unique('employee_id');
                });
            }

            if (! $this->foreignKeyExists('users', 'users_employee_id_foreign')) {
                Schema::table('users', function (Blueprint $table) {
                    $table->foreign('employee_id')->references('id')->on('employees')->cascadeOnDelete();
                });
            }
        }

        if (DB::getDriverName() !== 'sqlite') {
            DB::statement('ALTER TABLE `users` MODIFY `employee_id` BIGINT UNSIGNED NOT NULL');
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['employee_id']);
            $table->dropUnique(['employee_id']);
            $table->dropColumn('employee_id');
        });
    }

    private function indexExists(string $table, string $index): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $indexes = DB::select("PRAGMA index_list('{$table}')");

            return collect($indexes)->contains(fn ($row) => ($row->name ?? null) === $index);
        }

        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) as count FROM information_schema.statistics WHERE table_schema = ? AND table_name = ? AND index_name = ?',
            [$database, $table, $index],
        );

        return ((int) (Arr::get((array) $row, 'count') ?? 0)) > 0;
    }

    private function foreignKeyExists(string $table, string $foreignKey): bool
    {
        if (DB::getDriverName() === 'sqlite') {
            $foreignKeys = DB::select("PRAGMA foreign_key_list('{$table}')");

            return collect($foreignKeys)->contains(fn ($row) => ($row->id ?? null) === $foreignKey);
        }

        $database = DB::getDatabaseName();
        $row = DB::selectOne(
            'SELECT COUNT(*) as count FROM information_schema.table_constraints WHERE table_schema = ? AND table_name = ? AND constraint_name = ?',
            [$database, $table, $foreignKey],
        );

        return ((int) (Arr::get((array) $row, 'count') ?? 0)) > 0;
    }
};
