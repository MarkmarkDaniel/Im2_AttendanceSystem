<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // "Unofficially Dropped" = stopped attending without filing a drop
        $this->setAllowedStatuses(['Active', 'Inactive', 'Dropped', 'Unofficially Dropped']);
    }

    public function down(): void
    {
        // The old column cannot hold the new value, so fold it into "Dropped" first
        DB::table('students')
            ->where('status', 'Unofficially Dropped')
            ->update(['status' => 'Dropped']);

        $this->setAllowedStatuses(['Active', 'Inactive', 'Dropped']);
    }

    private function setAllowedStatuses(array $statuses): void
    {
        // PostgreSQL keeps an enum as a CHECK constraint, which ->change() cannot rewrite
        if (DB::getDriverName() === 'pgsql') {
            $list = implode(', ', array_map(fn ($status) => DB::getPdo()->quote($status), $statuses));

            DB::statement('ALTER TABLE students DROP CONSTRAINT IF EXISTS students_status_check');
            DB::statement("ALTER TABLE students ADD CONSTRAINT students_status_check CHECK (status IN ({$list}))");

            return;
        }

        Schema::table('students', function (Blueprint $table) use ($statuses) {
            $table->enum('status', $statuses)
                  ->default('Active')
                  ->change();
        });
    }
};
