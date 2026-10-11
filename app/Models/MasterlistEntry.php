<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * One row of the official student list. A student can only self-register
 * when their student number, last name and birth date match a row here.
 */
class MasterlistEntry extends Model
{
    public const YEAR_LEVELS = ['1st Year', '2nd Year', '3rd Year', '4th Year'];

    protected $table = 'student_masterlist';

    protected $fillable = [
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'birth_date',
        'department_id',
        'year_level',
        'section',
    ];

    protected function casts(): array
    {
        return [
            'birth_date' => 'date',
        ];
    }

    // ---------------- Relationships ----------------

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /**
     * The student record that already uses this student number, if any.
     * Covers both self-registered students and ones the admin created.
     */
    public function account(): HasOne
    {
        return $this->hasOne(Student::class, 'student_number', 'student_number');
    }

    // ---------------- Accessors ----------------

    public function getFormalNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    // ---------------- Query Scopes ----------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('student_number', 'like', "%{$term}%");
        });
    }

    // ---------------- Identity check ----------------

    /**
     * True when the details typed on the registration form belong to this entry.
     * Names are compared without regard to case or extra spaces.
     */
    public function matchesIdentity(string $lastName, string $birthDate): bool
    {
        return self::normalizeName($lastName) === self::normalizeName($this->last_name)
            && Carbon::parse($birthDate)->isSameDay($this->birth_date);
    }

    private static function normalizeName(string $name): string
    {
        return mb_strtolower(preg_replace('/\s+/', ' ', trim($name)));
    }
}
