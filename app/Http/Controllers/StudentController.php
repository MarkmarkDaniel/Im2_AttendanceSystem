<?php

namespace App\Http\Controllers;

use App\Http\Requests\StudentRequest;
use App\Models\Department;
use App\Models\Student;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class StudentController extends Controller
{
    public function index(Request $request): View
    {
        $filters = $request->only(['department_id', 'year_level', 'section', 'status']);
        $search  = $request->query('search');

        $students = Student::query()
            ->with(['department', 'user'])
            ->search($search)
            ->filter($filters)
            ->withCount('enrollments')
            ->orderBy('last_name')
            ->orderBy('first_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.students.index', [
            'students'    => $students,
            'departments' => Department::orderBy('department_code')->get(),
            'search'      => $search,
            'filters'     => $filters,
        ]);
    }

    public function create(): View
    {
        return view('admin.students.create', [
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function store(StudentRequest $request): RedirectResponse
    {
        $data = $request->validated();

        // User + Student must be created together or not at all
        DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => User::ROLE_STUDENT,
            ]);

            Student::create([
                'user_id'        => $user->id,
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'middle_name'    => $data['middle_name'] ?? null,
                'last_name'      => $data['last_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'department_id'  => $data['department_id'],
                'year_level'     => $data['year_level'],
                'section'        => $data['section'],
                'status'         => $data['status'],
            ]);
        });

        return redirect()->route('admin.students.index')
            ->with('success', 'Student "' . $data['student_number'] . '" was created.');
    }

    public function edit(Student $student): View
    {
        return view('admin.students.edit', [
            'student'     => $student,
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function update(StudentRequest $request, Student $student): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $student) {

            $userData = [
                'name'  => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $data['email'],
            ];

            // Only change the password if a new one was actually typed
            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $student->user->update($userData);

            $student->update([
                'student_number' => $data['student_number'],
                'first_name'     => $data['first_name'],
                'middle_name'    => $data['middle_name'] ?? null,
                'last_name'      => $data['last_name'],
                'email'          => $data['email'],
                'phone'          => $data['phone'] ?? null,
                'department_id'  => $data['department_id'],
                'year_level'     => $data['year_level'],
                'section'        => $data['section'],
                'status'         => $data['status'],
            ]);
        });

        // A new upload wins over the "remove" tick box
        if ($request->hasFile('profile_image')) {
            $student->user->storeProfileImage($request->file('profile_image'));
        } elseif ($request->boolean('remove_profile_image')) {
            $student->user->removeProfileImage();
        }

        return redirect()->route('admin.students.index')
            ->with('success', 'Student "' . $student->student_number . '" was updated.');
    }

    public function destroy(Student $student): RedirectResponse
    {
        // Deleting would cascade and wipe real attendance history
        if ($student->attendances()->exists()) {
            return redirect()->route('admin.students.index')
                ->with('error', 'Cannot delete "' . $student->student_number
                    . '" because attendance records exist. Set the status to Inactive, Dropped or Unofficially Dropped instead.');
        }

        $number = $student->student_number;

        // Deleting the user cascades to the student row (see the migration)
        DB::transaction(fn () => $student->user->delete());

        return redirect()->route('admin.students.index')
            ->with('success', 'Student "' . $number . '" was deleted.');
    }
}
