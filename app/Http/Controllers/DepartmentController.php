<?php

namespace App\Http\Controllers;

use App\Http\Requests\DepartmentRequest;
use App\Models\Department;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DepartmentController extends Controller
{
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $departments = Department::query()
            ->when($search, function ($query, $term) {
                $query->where(function ($q) use ($term) {
                    $q->whereLike('department_code', "%{$term}%")
                      ->orWhereLike('department_name', "%{$term}%");
                });
            })
            ->withCount(['students', 'instructors', 'subjects'])
            ->orderBy('department_code')
            ->paginate(10)
            ->withQueryString();

        return view('admin.departments.index', compact('departments', 'search'));
    }

    public function create(): View
    {
        return view('admin.departments.create');
    }

    public function store(DepartmentRequest $request): RedirectResponse
    {
        $department = Department::create($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department "' . $department->department_code . '" was created.');
    }

    public function edit(Department $department): View
    {
        return view('admin.departments.edit', compact('department'));
    }

    public function update(DepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department "' . $department->department_code . '" was updated.');
    }

    public function destroy(Department $department): RedirectResponse
    {
        // The migration uses restrictOnDelete, so check before attempting
        $inUse = $department->students()->exists()
              || $department->instructors()->exists()
              || $department->subjects()->exists();

        if ($inUse) {
            return redirect()->route('admin.departments.index')
                ->with('error', 'Cannot delete "' . $department->department_code
                    . '" because students, instructors, or subjects are still assigned to it.');
        }

        $code = $department->department_code;
        $department->delete();

        return redirect()->route('admin.departments.index')
            ->with('success', 'Department "' . $code . '" was deleted.');
    }
}
