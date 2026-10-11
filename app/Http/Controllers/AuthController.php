<?php

namespace App\Http\Controllers;

use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\MasterlistEntry;
use App\Models\Notification;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    // ---------------- LOGIN ----------------

    public function showLogin(): View
    {
        return view('auth.login');
    }

    public function login(LoginRequest $request): RedirectResponse
    {
        $credentials = $request->only('email', 'password');

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            return back()
                ->withInput($request->only('email'))
                ->with('error', 'The email or password you entered is incorrect.');
        }

        $user = Auth::user();

        // Students whose record is not Active are still let in. The
        // RestrictInactiveStudent middleware then keeps them on their
        // dashboard, behind a pop-up that explains their status.

        // Block instructors who are marked inactive
        if ($user->isInstructor() && $user->instructor && $user->instructor->status !== 'Active') {
            Auth::logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return back()
                ->withInput($request->only('email'))
                ->with('error', 'Your instructor account is inactive. Please contact the administrator.');
        }

        // Prevents session fixation attacks
        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'))
            ->with('success', 'Welcome back, ' . $user->name . '!');
    }

    // ---------------- REGISTRATION ----------------

    public function showRegister(): View
    {
        return view('auth.register');
    }

    /**
     * Self-registration. Only a student on the admin's masterlist can create
     * an account, and only by proving the student number is theirs (last name
     * and birth date). Name, department, year level and section are copied
     * from the masterlist, never taken from the form.
     */
    public function register(RegisterRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // A transaction means: if ANY step fails, none of them are saved.
        // Without this you could end up with a user account and no student row.
        DB::transaction(function () use ($data) {

            // Locked so two people cannot claim the same student number at once
            $entry = MasterlistEntry::where('student_number', trim($data['student_number']))
                ->lockForUpdate()
                ->first();

            // One message for "not on the list" and "details do not match",
            // so nobody can use this form to find out which numbers exist.
            if (! $entry || ! $entry->matchesIdentity($data['last_name'], $data['birth_date'])) {
                throw ValidationException::withMessages([
                    'student_number' => 'The student number, last name and birth date do not match our records. '
                        . 'Check your details or contact the administrator.',
                ]);
            }

            if ($entry->account()->exists()) {
                throw ValidationException::withMessages([
                    'student_number' => 'This student number already has an account. '
                        . 'If this was not you, contact the administrator.',
                ]);
            }

            $user = User::create([
                'name'     => trim($entry->first_name . ' ' . $entry->last_name),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => User::ROLE_STUDENT,
            ]);

            Student::create([
                'user_id'        => $user->id,
                'student_number' => $entry->student_number,
                'first_name'     => $entry->first_name,
                'middle_name'    => $entry->middle_name,
                'last_name'      => $entry->last_name,
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'department_id'  => $entry->department_id,
                'year_level'     => $entry->year_level,
                'section'        => $entry->section,
                'status'         => 'Active',
            ]);

            // Welcome notification for the new student
            Notification::send(
                $user->id,
                'Welcome to the Attendance System',
                'Your account has been created. You can now scan QR codes to record your attendance.',
                'success'
            );

            // Tell every admin about the new registration
            $admins = User::where('role', User::ROLE_ADMIN)->get();

            foreach ($admins as $admin) {
                Notification::send(
                    $admin->id,
                    'New Student Registration',
                    $user->name . ' (' . $entry->student_number . ') completed registration.',
                    'info'
                );
            }
        });

        return redirect()->route('login')
            ->with('success', 'Registration successful. You may now log in.');
    }

    // ---------------- LOGOUT ----------------

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out.');
    }
}
