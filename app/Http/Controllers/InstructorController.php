<?php

namespace App\Http\Controllers;

use App\Http\Requests\InstructorRequest;
use App\Models\Department;
use App\Models\Instructor;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class InstructorController extends Controller
{
    public function index(Request $request): View
    {
        $search       = $request->query('search');
        $departmentId = $request->query('department_id');
        $status       = $request->query('status');

        $instructors = Instructor::query()
            ->with(['department', 'user'])
            ->search($search)
            ->when($departmentId, fn ($q, $v) => $q->where('department_id', $v))
            ->when($status, fn ($q, $v) => $q->where('status', $v))
            ->withCount('classes')
            ->orderBy('last_name')
            ->paginate(10)
            ->withQueryString();

        return view('admin.instructors.index', [
            'instructors'  => $instructors,
            'departments'  => Department::orderBy('department_code')->get(),
            'search'       => $search,
            'departmentId' => $departmentId,
            'status'       => $status,
        ]);
    }

    public function create(): View
    {
        return view('admin.instructors.create', [
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function store(InstructorRequest $request): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data) {

            $user = User::create([
                'name'     => trim($data['first_name'] . ' ' . $data['last_name']),
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
                'role'     => User::ROLE_INSTRUCTOR,
            ]);

            Instructor::create([
                'user_id'         => $user->id,
                'employee_number' => $data['employee_number'],
                'first_name'      => $data['first_name'],
                'middle_name'     => $data['middle_name'] ?? null,
                'last_name'       => $data['last_name'],
                'email'           => $data['email'],
                'department_id'   => $data['department_id'],
                'status'          => $data['status'],
            ]);
        });

        return redirect()->route('admin.instructors.index')
            ->with('success', 'Instructor "' . $data['employee_number'] . '" was created.');
    }

    public function edit(Instructor $instructor): View
    {
        return view('admin.instructors.edit', [
            'instructor'  => $instructor,
            'departments' => Department::orderBy('department_code')->get(),
        ]);
    }

    public function update(InstructorRequest $request, Instructor $instructor): RedirectResponse
    {
        $data = $request->validated();

        DB::transaction(function () use ($data, $instructor) {

            $userData = [
                'name'  => trim($data['first_name'] . ' ' . $data['last_name']),
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $userData['password'] = Hash::make($data['password']);
            }

            $instructor->user->update($userData);

            $instructor->update([
                'employee_number' => $data['employee_number'],
                'first_name'      => $data['first_name'],
                'middle_name'     => $data['middle_name'] ?? null,
                'last_name'       => $data['last_name'],
                'email'           => $data['email'],
                'department_id'   => $data['department_id'],
                'status'          => $data['status'],
            ]);
        });

        // A new upload wins over the "remove" tick box
        if ($request->hasFile('profile_image')) {
            $instructor->user->storeProfileImage($request->file('profile_image'));
        } elseif ($request->boolean('remove_profile_image')) {
            $instructor->user->removeProfileImage();
        }

        return redirect()->route('admin.instructors.index')
            ->with('success', 'Instructor "' . $instructor->employee_number . '" was updated.');
    }

    public function destroy(Instructor $instructor): RedirectResponse
    {
        if ($instructor->classes()->exists()) {
            return redirect()->route('admin.instructors.index')
                ->with('error', 'Cannot delete "' . $instructor->employee_number
                    . '" because classes are still assigned. Reassign the classes first, or set the status to Inactive.');
        }

        $number = $instructor->employee_number;

        DB::transaction(fn () => $instructor->user->delete());

        return redirect()->route('admin.instructors.index')
            ->with('success', 'Instructor "' . $number . '" was deleted.');
    }
}
