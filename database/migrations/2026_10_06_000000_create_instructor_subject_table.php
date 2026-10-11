<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('instructor_subject', function (Blueprint $table) {
            $table->id();

            $table->foreignId('instructor_id')
                  ->constrained('instructors')
                  ->cascadeOnDelete();

            $table->foreignId('subject_id')
                  ->constrained('subjects')
                  ->cascadeOnDelete();

            $table->timestamps();

            // A subject can only be assigned once to an instructor
            $table->unique(['instructor_id', 'subject_id'], 'instructor_subject_unique');
        });

        // Instructors already teaching a class keep that subject assigned
        $now = now();

        $pairs = DB::table('classes')
            ->select('instructor_id', 'subject_id')
            ->distinct()
            ->get()
            ->map(fn ($row) => [
                'instructor_id' => $row->instructor_id,
                'subject_id'    => $row->subject_id,
                'created_at'    => $now,
                'updated_at'    => $now,
            ])
            ->all();

        DB::table('instructor_subject')->insert($pairs);
    }

    public function down(): void
    {
        Schema::dropIfExists('instructor_subject');
    }
};
