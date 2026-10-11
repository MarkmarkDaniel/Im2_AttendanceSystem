<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\Instructor;
use App\Models\Subject;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class InstructorSubjectController extends Controller
{
    /**
     * Manage the subjects assigned to one instructor.
     */
    public function manage(Request $request, Instructor $instructor): View
    {
        $instructor->load('department');

        $assigned = $instructor->subjects()
            ->with('department')
            // Only the classes this instructor teaches under the subject
            ->withCount(['classes' => fn ($q) => $q->where('classes.instructor_id', $instructor->id)])
            ->orderBy('subject_code')
            ->get();

        $assignedIds = $assigned->pluck('id')->all();

        // Subjects that are NOT yet assigned to this instructor
        $availableSearch = $request->query('available_search');
        $availableDept   = $request->query('available_department');

        $available = Subject::query()
            ->with('department')
            ->when($assignedIds, fn ($q) => $q->whereNotIn('id', $assignedIds))
            ->search($availableSearch)
            ->when($availableDept, fn ($q, $v) => $q->where('department_id', $v))
            ->orderBy('subject_code')
            ->get();

        return view('admin.instructors.subjects', [
            'instructor'      => $instructor,
            'assigned'        => $assigned,
            'available'       => $available,
            'departments'     => Department::orderBy('department_code')->get(),
            'availableSearch' => $availableSearch,
            'availableDept'   => $availableDept,
        ]);
    }

    /**
     * Assign one or more subjects to the instructor.
     */
    public function store(Request $request, Instructor $instructor): RedirectResponse
    {
        $validated = $request->validate([
            'subject_ids'   => ['required', 'array', 'min:1'],
            'subject_ids.*' => ['integer', 'exists:subjects,id'],
        ], [
            'subject_ids.required' => 'Select at least one subject to assign.',
        ]);

        // syncWithoutDetaching skips subjects that are already assigned,
        // so re-submitting the same subject never creates a duplicate
        $changes = $instructor->subjects()->syncWithoutDetaching($validated['subject_ids']);

        return redirect()->route('admin.instructors.subjects', $instructor)
            ->with('success', count($changes['attached']) . ' subject(s) assigned to '
                . $instructor->full_name . '.');
    }

    /**
     * Remove one subject from the instructor.
     */
    public function destroy(Instructor $instructor, Subject $subject): RedirectResponse
    {
        $hasClasses = $instructor->classes()
            ->where('subject_id', $subject->id)
            ->exists();

        if ($hasClasses) {
            return redirect()->route('admin.instructors.subjects', $instructor)
                ->with('error', 'Cannot remove "' . $subject->subject_code . '" because '
                    . $instructor->full_name . ' still has a class under it. Reassign or delete the class first.');
        }

        $instructor->subjects()->detach($subject->id);

        return redirect()->route('admin.instructors.subjects', $instructor)
            ->with('success', '"' . $subject->subject_code . '" was removed from '
                . $instructor->full_name . '.');
    }
}
