<?php

namespace App\Http\Controllers;

use App\Http\Requests\ClassRequest;
use App\Models\ClassModel;
use App\Models\Instructor;
use App\Models\Subject;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClassController extends Controller
{
    public function index(Request $request): View
    {
        $search       = $request->query('search');
        $instructorId = $request->query('instructor_id');
        $day          = $request->query('schedule_day');

        $classes = ClassModel::query()
            ->with(['subject', 'instructor'])
            ->when($search, function (Builder $query, $term) {
                $query->whereHas('subject', function (Builder $q) use ($term) {
                    $q->whereLike('subject_code', "%{$term}%")
                      ->orWhereLike('subject_name', "%{$term}%");
                })->orWhereLike('section', "%{$term}%");
            })
            ->when($instructorId, fn (Builder $q, $v) => $q->where('instructor_id', $v))
            ->when($day, fn (Builder $q, $v) => $q->where('schedule_day', $v))
            ->withCount(['enrollments', 'attendanceSessions'])
            ->orderBy('schedule_day')
            ->orderBy('start_time')
            ->paginate(10)
            ->withQueryString();

        return view('admin.classes.index', [
            'classes'      => $classes,
            'instructors'  => Instructor::orderBy('last_name')->get(),
            'search'       => $search,
            'instructorId' => $instructorId,
            'day'          => $day,
        ]);
    }

    public function create(): View
    {
        return view('admin.classes.create', $this->formOptions());
    }

    public function store(ClassRequest $request): RedirectResponse
    {
        $class = ClassModel::create($request->validated());

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class "' . $class->class_title . '" was created.');
    }

    public function edit(ClassModel $class): View
    {
        return view('admin.classes.edit', array_merge(
            $this->formOptions(),
            ['class' => $class]
        ));
    }

    public function update(ClassRequest $request, ClassModel $class): RedirectResponse
    {
        $class->update($request->validated());

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class "' . $class->class_title . '" was updated.');
    }

    public function destroy(ClassModel $class): RedirectResponse
    {
        if ($class->attendanceSessions()->exists()) {
            return redirect()->route('admin.classes.index')
                ->with('error', 'Cannot delete this class because attendance sessions exist. '
                    . 'Set the status to Inactive instead.');
        }

        $title = $class->class_title;
        $class->delete();

        return redirect()->route('admin.classes.index')
            ->with('success', 'Class "' . $title . '" was deleted.');
    }

    /**
     * Dropdown data shared by the create and edit forms.
     */
    private function formOptions(): array
    {
        return [
            'subjects'    => Subject::orderBy('subject_code')->get(),
            'instructors' => Instructor::where('status', 'Active')->orderBy('last_name')->get(),
            'days'        => ['Monday', 'Tuesday', 'Wednesday', 'Thursday',
                              'Friday', 'Saturday', 'Sunday'],
            'semesters'   => ['1st Semester', '2nd Semester', 'Summer'],
        ];
    }
}
