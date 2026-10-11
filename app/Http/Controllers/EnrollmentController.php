<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Department;
use App\Models\Enrollment;
use App\Models\Notification;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class EnrollmentController extends Controller
{
    /**
     * Class picker — every class with its enrollment count.
     */
    public function index(Request $request): View
    {
        $search = $request->query('search');

        $classes = ClassModel::query()
            ->with(['subject', 'instructor'])
            ->when($search, function ($query, $term) {
                $query->whereHas('subject', fn ($q) =>
                    $q->whereLike('subject_code', "%{$term}%")
                      ->orWhereLike('subject_name', "%{$term}%")
                )->orWhereLike('section', "%{$term}%");
            })
            ->withCount('enrollments')
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return view('admin.enrollments.index', compact('classes', 'search'));
    }

    /**
     * Manage the roster of one class.
     */
    public function manage(Request $request, ClassModel $class): View
    {
        $class->load(['subject', 'instructor']);

        $enrollments = Enrollment::with('student.department')
            ->where('class_id', $class->id)
            ->join('students', 'students.id', '=', 'enrollments.student_id')
            ->orderBy('students.last_name')
            ->select('enrollments.*')
            ->get();

        $enrolledIds = $enrollments->pluck('student_id')->all();

        // Students who are NOT yet in this class
        $availableSearch = $request->query('available_search');
        $availableDept   = $request->query('available_department');

        $available = Student::query()
            ->with('department')
            ->where('status', 'Active')
            ->when($enrolledIds, fn ($q) => $q->whereNotIn('id', $enrolledIds))
            ->search($availableSearch)
            ->when($availableDept, fn ($q, $v) => $q->where('department_id', $v))
            ->orderBy('last_name')
            ->limit(50)
            ->get();

        return view('admin.enrollments.manage', [
            'class'           => $class,
            'enrollments'     => $enrollments,
            'available'       => $available,
            'departments'     => Department::orderBy('department_code')->get(),
            'availableSearch' => $availableSearch,
            'availableDept'   => $availableDept,
        ]);
    }

    /**
     * Enroll one or more students into the class.
     */
    public function store(Request $request, ClassModel $class): RedirectResponse
    {
        // Needed by class_title and schedule_text inside the notifications
        $class->load(['subject', 'instructor']);

        $validated = $request->validate([
            'student_ids'   => ['required', 'array', 'min:1'],
            'student_ids.*' => ['integer', 'exists:students,id'],
        ], [
            'student_ids.required' => 'Select at least one student to enroll.',
        ]);

        $added = 0;

        DB::transaction(function () use ($validated, $class, &$added) {
            foreach ($validated['student_ids'] as $studentId) {

                // firstOrCreate respects the UNIQUE (class_id, student_id) key,
                // so re-submitting the same student never creates a duplicate
                $enrollment = Enrollment::firstOrCreate(
                    [
                        'class_id'   => $class->id,
                        'student_id' => $studentId,
                    ],
                    ['enrollment_status' => 'Enrolled']
                );

                // Only notify on a genuinely new enrollment
                if (! $enrollment->wasRecentlyCreated) {
                    continue;
                }

                $added++;

                $student = Student::find($studentId);

                if (! $student) {
                    continue;
                }

                // ---------------- Tell the student ----------------
                if ($student->user_id) {
                    Notification::send(
                        $student->user_id,
                        'Enrolled in a New Class',
                        'You have been enrolled in ' . $class->class_title
                            . ' (' . $class->schedule_text . ').',
                        'success'
                    );
                }

                // ---------------- Tell the instructor ----------------
                if ($class->instructor?->user_id) {
                    Notification::send(
                        $class->instructor->user_id,
                        'New Enrollment',
                        $student->full_name . ' (' . $student->student_number
                            . ') was enrolled in ' . $class->class_title . '.',
                        'info'
                    );
                }
            }
        });

        return redirect()->route('admin.enrollments.manage', $class)
            ->with('success', $added . ' student(s) enrolled in ' . $class->class_title . '.');
    }

    /**
     * Change one enrollment's status (Enrolled / Dropped / Completed).
     */
    public function updateStatus(Request $request, Enrollment $enrollment): RedirectResponse
    {
        $validated = $request->validate([
            'enrollment_status' => ['required', Rule::in(['Enrolled', 'Dropped', 'Completed'])],
        ]);

        $enrollment->update($validated);

        // Let the student know their status changed
        $enrollment->load(['student', 'classModel.subject']);

        if ($enrollment->student?->user_id) {
            Notification::send(
                $enrollment->student->user_id,
                'Enrollment Status Updated',
                'Your enrollment in ' . $enrollment->classModel->class_title
                    . ' is now marked as ' . $validated['enrollment_status'] . '.',
                $validated['enrollment_status'] === 'Dropped' ? 'warning' : 'info'
            );
        }

        return redirect()->route('admin.enrollments.manage', $enrollment->class_id)
            ->with('success', 'Enrollment status updated to ' . $validated['enrollment_status'] . '.');
    }

    /**
     * Remove a student from the class entirely.
     */
    public function destroy(Enrollment $enrollment): RedirectResponse
    {
        $classId = $enrollment->class_id;
        $name    = $enrollment->student->full_name;

        // Attendance records exist independently of enrollment, so warn first
        $hasAttendance = $enrollment->student
            ->attendances()
            ->where('class_id', $classId)
            ->exists();

        if ($hasAttendance) {
            return redirect()->route('admin.enrollments.manage', $classId)
                ->with('error', 'Cannot remove ' . $name
                    . ' because attendance records exist for this class. Set the status to Dropped instead.');
        }

        $enrollment->delete();

        return redirect()->route('admin.enrollments.manage', $classId)
            ->with('success', $name . ' was removed from the class.');
    }
}
