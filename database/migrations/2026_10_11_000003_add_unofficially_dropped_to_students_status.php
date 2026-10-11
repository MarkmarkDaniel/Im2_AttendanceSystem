<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('students', function (Blueprint $table) {
            // "Unofficially Dropped" = stopped attending without filing a drop
            $table->enum('status', ['Active', 'Inactive', 'Dropped', 'Unofficially Dropped'])
                  ->default('Active')
                  ->change();
        });
    }

    public function down(): void
    {
        // The old column cannot hold the new value, so fold it into "Dropped" first
        DB::table('students')
            ->where('status', 'Unofficially Dropped')
            ->update(['status' => 'Dropped']);

        Schema::table('students', function (Blueprint $table) {
            $table->enum('status', ['Active', 'Inactive', 'Dropped'])
                  ->default('Active')
                  ->change();
        });
    }
};
