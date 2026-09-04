<?php

namespace App\Models;

use App\Mail\PasswordResetMail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Support\Facades\Mail;

class User extends Authenticatable
{
    use HasFactory;

    public const ROLE_ADMIN      = 'admin';
    public const ROLE_INSTRUCTOR = 'instructor';
    public const ROLE_STUDENT    = 'student';

    protected $fillable = [
        'name',
        'email',
        'password',
        'role',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password'          => 'hashed',
        ];
    }

    // ---------------- Relationships ----------------

    public function student(): HasOne
    {
        return $this->hasOne(Student::class);
    }

    public function instructor(): HasOne
    {
        return $this->hasOne(Instructor::class);
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class)->latest();
    }

    public function unreadNotifications(): HasMany
    {
        return $this->hasMany(Notification::class)
                    ->where('is_read', false)
                    ->latest();
    }

    public function recordedAttendances(): HasMany
    {
        return $this->hasMany(Attendance::class, 'recorded_by');
    }

    // ---------------- Role helpers ----------------

    public function isAdmin(): bool
    {
        return $this->role === self::ROLE_ADMIN;
    }

    public function isInstructor(): bool
    {
        return $this->role === self::ROLE_INSTRUCTOR;
    }

    public function isStudent(): bool
    {
        return $this->role === self::ROLE_STUDENT;
    }

    /**
     * Where this user goes after logging in.
     */
    public function dashboardRoute(): string
    {
        return match ($this->role) {
            self::ROLE_ADMIN      => 'admin.dashboard',
            self::ROLE_INSTRUCTOR => 'instructor.dashboard',
            default               => 'student.dashboard',
        };
    }

    /**
     * Sends the branded "Change Password" email instead of Laravel's
     * default plain-text notification.
     */
    public function sendPasswordResetNotification($token): void
    {
        $resetUrl = route('password.reset', [
            'token' => $token,
            'email' => $this->email,
        ]);

        $expiresAt = now()
            ->addMinutes((int) config('auth.passwords.users.expire', 60))
            ->format('F j, Y \a\t g:i A');

        Mail::to($this->email)->send(new PasswordResetMail($this, $resetUrl, $expiresAt));
    }
}
