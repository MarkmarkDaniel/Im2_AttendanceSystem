<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Official list of enrolled students, encoded by the admin.
        // Self-registration is only allowed for a student number on this list.
        Schema::create('student_masterlist', function (Blueprint $table) {
            $table->id();

            $table->string('student_number', 30)->unique();
            $table->string('first_name', 100);
            $table->string('middle_name', 100)->nullable();
            $table->string('last_name', 100);

            // Asked during registration to prove the person owns the student number
            $table->date('birth_date');

            $table->foreignId('department_id')
                  ->constrained('departments')
                  ->restrictOnDelete();

            $table->enum('year_level', ['1st Year', '2nd Year', '3rd Year', '4th Year']);
            $table->string('section', 20);
            $table->timestamps();

            $table->index('last_name', 'student_masterlist_last_name_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_masterlist');
    }
};
