<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Student extends Model
{
    use HasFactory;

    public const STATUSES = ['Active', 'Inactive', 'Dropped', 'Unofficially Dropped'];

    protected $fillable = [
        'user_id',
        'student_number',
        'first_name',
        'middle_name',
        'last_name',
        'email',
        'phone',
        'department_id',
        'year_level',
        'section',
        'status',
    ];

    // ---------------- Relationships ----------------

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(Enrollment::class);
    }

    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // ---------------- Status ----------------

    public function isActive(): bool
    {
        return $this->status === 'Active';
    }

    /**
     * What a student who is not Active is told on the account status notice:
     * a one-line explanation and the steps to fix it.
     *
     * @return array{summary: string, steps: string[]}
     */
    public function statusNotice(): array
    {
        return match ($this->status) {
            'Inactive' => [
                'summary' => 'Your student record is currently inactive, so attendance cannot be recorded for you.',
                'steps'   => [
                    'Go to the Registrar\'s Office and confirm your enrollment for this term.',
                    'Settle any pending requirements they tell you about.',
                    'Once the registrar reactivates your record, log in again.',
                ],
            ],
            'Dropped' => [
                'summary' => 'You have been officially dropped, so you no longer have access to your classes and attendance.',
                'steps'   => [
                    'If you believe this is a mistake, bring your student ID to the Registrar\'s Office.',
                    'If you want to return, ask the registrar about re-enrollment or re-admission.',
                ],
            ],
            'Unofficially Dropped' => [
                'summary' => 'You have been marked as unofficially dropped because you stopped attending without filing a drop.',
                'steps'   => [
                    'See your instructor or class adviser to explain your absences.',
                    'Go to the Registrar\'s Office to either be reinstated or file an official drop.',
                    'Do this as soon as possible so your record can be settled.',
                ],
            ],
            default => [
                'summary' => 'Your student record is not active.',
                'steps'   => ['Please contact the Registrar\'s Office.'],
            ],
        };
    }

    // ---------------- Accessors ----------------

    public function getFullNameAttribute(): string
    {
        $middle = $this->middle_name ? ' ' . $this->middle_name : '';

        return $this->first_name . $middle . ' ' . $this->last_name;
    }

    public function getFormalNameAttribute(): string
    {
        return $this->last_name . ', ' . $this->first_name;
    }

    // ---------------- Query Scopes (search & filters) ----------------

    public function scopeSearch(Builder $query, ?string $term): Builder
    {
        if (blank($term)) {
            return $query;
        }

        return $query->where(function (Builder $q) use ($term) {
            $q->where('first_name', 'like', "%{$term}%")
              ->orWhere('last_name', 'like', "%{$term}%")
              ->orWhere('student_number', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%");
        });
    }

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['department_id'] ?? null,
                fn (Builder $q, $v) => $q->where('department_id', $v))
            ->when($filters['year_level'] ?? null,
                fn (Builder $q, $v) => $q->where('year_level', $v))
            ->when($filters['section'] ?? null,
                fn (Builder $q, $v) => $q->where('section', $v))
            ->when($filters['status'] ?? null,
                fn (Builder $q, $v) => $q->where('status', $v));
    }

    /**
     * Overall attendance rate across every class.
     * Formula: ((Present + Late) / Total Records) * 100
     */
    public function attendanceRate(): float
    {
        $total = $this->attendances()->count();

        if ($total === 0) {
            return 0.0; // prevents division by zero
        }

        $good = $this->attendances()
                     ->whereIn('status', ['Present', 'Late'])
                     ->count();

        return round(($good / $total) * 100, 2);
    }
}
