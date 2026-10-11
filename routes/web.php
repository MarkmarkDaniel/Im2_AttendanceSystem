<?php

use App\Http\Controllers\AdminDashboardController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\ClassController;
use App\Http\Controllers\DepartmentController;
use App\Http\Controllers\EnrollmentController;
use App\Http\Controllers\InstructorController;
use App\Http\Controllers\StudentController;
use App\Http\Controllers\StudentMasterlistController;
use App\Http\Controllers\SubjectController;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AttendanceController;
use App\Http\Controllers\AttendanceSessionController;
use App\Http\Controllers\InstructorClassController;
use App\Http\Controllers\InstructorDashboardController;
use App\Http\Controllers\QRAttendanceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\PasswordResetController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\StudentDashboardController;
use App\Http\Controllers\StudentPortalController;
use App\Http\Controllers\ReportController;
use App\Http\Controllers\ReportExportController;
/*
|--------------------------------------------------------------------------
| Landing
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    return Auth::check()
        ? redirect()->route('dashboard')
        : redirect()->route('login');
});


/*
|--------------------------------------------------------------------------
| Guest routes
|--------------------------------------------------------------------------
*/

Route::middleware('guest')->group(function () {
    Route::get('/login',  [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->name('login.attempt');

    Route::get('/register',  [AuthController::class, 'showRegister'])->name('register');
    // Throttled so the birth date check cannot be guessed by brute force
    Route::post('/register', [AuthController::class, 'register'])
        ->middleware('throttle:6,1')
        ->name('register.store');

    Route::get('/forgot-password',  [PasswordResetController::class, 'showRequestForm'])->name('password.request');
    Route::post('/forgot-password', [PasswordResetController::class, 'sendResetLink'])->name('password.email');
});

/*
|--------------------------------------------------------------------------
| Change password link (open to guests AND logged-in users)
|--------------------------------------------------------------------------
| These must sit outside the "guest" group. A student who asks for the link
| from the menu is still logged in when the emailed button opens this page,
| and "guest" would bounce them straight to the dashboard.
*/

Route::get('/reset-password/{token}', [PasswordResetController::class, 'showResetForm'])->name('password.reset');
Route::post('/reset-password', [PasswordResetController::class, 'reset'])->name('password.update');


/*
|--------------------------------------------------------------------------
| Logout and role router
|--------------------------------------------------------------------------
*/

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::get('/dashboard', function () {
    return redirect()->route(Auth::user()->dashboardRoute());
})->middleware('auth')->name('dashboard');


/*
|--------------------------------------------------------------------------
| ADMIN ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:admin'])
    ->prefix('admin')
    ->name('admin.')
    ->group(function () {

        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::resource('departments', DepartmentController::class)->except('show');
        Route::resource('subjects',    SubjectController::class)->except('show');
        Route::resource('students',    StudentController::class)->except('show');

        // Official student list that self-registration is checked against
        Route::get('masterlist',            [StudentMasterlistController::class, 'index'])->name('masterlist.index');
        Route::post('masterlist',           [StudentMasterlistController::class, 'store'])->name('masterlist.store');
        Route::post('masterlist/import',    [StudentMasterlistController::class, 'import'])->name('masterlist.import');
        Route::get('masterlist/template',   [StudentMasterlistController::class, 'template'])->name('masterlist.template');
        Route::delete('masterlist/{entry}', [StudentMasterlistController::class, 'destroy'])->name('masterlist.destroy');
        Route::resource('instructors', InstructorController::class)->except('show');
        Route::resource('classes',     ClassController::class)->except('show');

        // Enrollment management (many-to-many)
        Route::get('enrollments',                  [EnrollmentController::class, 'index'])->name('enrollments.index');
        Route::get('enrollments/{class}',          [EnrollmentController::class, 'manage'])->name('enrollments.manage');
        Route::post('enrollments/{class}',         [EnrollmentController::class, 'store'])->name('enrollments.store');
        Route::patch('enrollment/{enrollment}',    [EnrollmentController::class, 'updateStatus'])->name('enrollments.status');
        Route::delete('enrollment/{enrollment}',   [EnrollmentController::class, 'destroy'])->name('enrollments.destroy');

                // Reports
        Route::get('/reports',            [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/daily',      [ReportController::class, 'daily'])->name('reports.daily');
        Route::get('/reports/student',    [ReportController::class, 'student'])->name('reports.student');
        Route::get('/reports/class',      [ReportController::class, 'class'])->name('reports.class');
        Route::get('/reports/department', [ReportController::class, 'department'])->name('reports.department');
        Route::get('/reports/monthly',    [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/export/csv', [ReportExportController::class, 'csv'])->name('reports.csv');
        Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->name('reports.pdf');

    });


/*
|--------------------------------------------------------------------------
| INSTRUCTOR ROUTES
|--------------------------------------------------------------------------
*/

Route::middleware(['auth', 'role:instructor'])
    ->prefix('instructor')
    ->name('instructor.')
    ->group(function () {

        Route::get('/dashboard', [InstructorDashboardController::class, 'index'])->name('dashboard');

        // My classes
        Route::get('/classes',         [InstructorClassController::class, 'index'])->name('classes.index');
        Route::get('/classes/{class}', [InstructorClassController::class, 'show'])->name('classes.show');

        // Attendance sessions
        Route::post('/sessions/open/{class}',    [AttendanceSessionController::class, 'open'])->name('sessions.open');
        Route::post('/sessions/close/{session}', [AttendanceSessionController::class, 'close'])->name('sessions.close');

        // Manual attendance
        Route::get('/attendance/record/{class}',  [AttendanceController::class, 'record'])->name('attendance.record');
        Route::post('/attendance/record/{class}', [AttendanceController::class, 'store'])->name('attendance.store');

        // Daily monitoring
        Route::get('/attendance/daily', [AttendanceController::class, 'daily'])->name('attendance.daily');
        // QR attendance
        Route::get('/qr',                    [QRAttendanceController::class, 'index'])->name('qr.index');
        Route::get('/qr/{class}',            [QRAttendanceController::class, 'show'])->name('qr.show');
        Route::get('/qr/stats/{session}',    [QRAttendanceController::class, 'stats'])->name('qr.stats');
        Route::get('/qr/refresh/{session}', [QRAttendanceController::class, 'refreshQr'])->name('qr.refresh');
                // Reports
                // Reports
        Route::get('/reports',            [ReportController::class, 'index'])->name('reports.index');
        Route::get('/reports/daily',      [ReportController::class, 'daily'])->name('reports.daily');
        Route::get('/reports/student',    [ReportController::class, 'student'])->name('reports.student');
        Route::get('/reports/class',      [ReportController::class, 'class'])->name('reports.class');
        Route::get('/reports/monthly',    [ReportController::class, 'monthly'])->name('reports.monthly');
        Route::get('/reports/export/csv', [ReportExportController::class, 'csv'])->name('reports.csv');
        Route::get('/reports/export/pdf', [ReportExportController::class, 'pdf'])->name('reports.pdf');

    });

/*
|--------------------------------------------------------------------------
| STUDENT ROUTES
|--------------------------------------------------------------------------
*/



Route::middleware(['auth', 'role:student'])
    ->prefix('student')
    ->name('student.')
    ->group(function () {

        Route::get('/dashboard',  [StudentDashboardController::class, 'index'])->name('dashboard');
        Route::get('/subjects',   [StudentPortalController::class, 'subjects'])->name('subjects');
        Route::get('/attendance', [StudentPortalController::class, 'attendance'])->name('attendance');

        // QR scanning
        Route::get('/qr',              [QRAttendanceController::class, 'scanner'])->name('qr.scanner');
        Route::post('/qr/submit',      [QRAttendanceController::class, 'submit'])->name('qr.submit');
        Route::get('/qr/scan/{token}', [QRAttendanceController::class, 'scan'])->name('qr.scan');

    });


/*
|--------------------------------------------------------------------------
| SHARED ROUTES (all three roles)
|--------------------------------------------------------------------------
*/

Route::middleware('auth')->group(function () {

    // Notifications
    Route::get('/notifications',                    [NotificationController::class, 'index'])->name('notifications.index');
    Route::patch('/notifications/{notification}',   [NotificationController::class, 'markAsRead'])->name('notifications.read');
    Route::post('/notifications/read-all',          [NotificationController::class, 'markAllAsRead'])->name('notifications.readAll');
    Route::delete('/notifications/{notification}',  [NotificationController::class, 'destroy'])->name('notifications.destroy');

    // Profile
    Route::get('/profile',           [ProfileController::class, 'show'])->name('profile.show');
    Route::patch('/profile/password',[ProfileController::class, 'updatePassword'])->name('profile.password');

    // Profile image (admins only - enforced in the controller)
    Route::post('/profile/image',    [ProfileController::class, 'updateImage'])->name('profile.image');
    Route::delete('/profile/image',  [ProfileController::class, 'destroyImage'])->name('profile.image.destroy');

    // Students and instructors change their password through an emailed link
    Route::post('/profile/change-password-link', [PasswordResetController::class, 'sendLinkToSelf'])
        ->name('password.email.self');

});
