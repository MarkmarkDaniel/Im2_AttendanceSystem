@extends('layouts.app')

@section('title', 'Assign Subjects')
@section('page-title', 'Assign Subjects')
@section('page-subtitle', $instructor->full_name)

@section('page-actions')
    <a href="{{ route('admin.instructors.index') }}" class="btn btn-outline-secondary btn-sm">
        <i class="bi bi-arrow-left me-1"></i>Back to Instructors
    </a>
@endsection

@section('content')

{{-- ==================== INSTRUCTOR INFO ==================== --}}
<div class="card mb-3">
    <div class="card-body">
        <div class="row g-3 small">
            <div class="col-md-3">
                <div class="text-muted">Employee No.</div>
                <div class="fw-medium">{{ $instructor->employee_number }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Email</div>
                <div class="fw-medium">{{ $instructor->email }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Department</div>
                <div class="fw-medium">{{ $instructor->department->department_name }}</div>
            </div>
            <div class="col-md-3">
                <div class="text-muted">Status</div>
                <span class="badge bg-{{ $instructor->status === 'Active' ? 'success' : 'secondary' }}">
                    {{ $instructor->status }}
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row g-3">

    {{-- ==================== ASSIGNED SUBJECTS ==================== --}}
    <div class="col-lg-7">
        <div class="card h-100">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <span class="fw-medium">Assigned Subjects</span>
                <span class="badge bg-primary">{{ $assigned->count() }}</span>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Code</th>
                            <th>Subject Name</th>
                            <th class="text-center">Classes</th>
                            <th class="text-end no-print">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($assigned as $subject)
                            <tr>
                                <td><span class="badge bg-dark">{{ $subject->subject_code }}</span></td>
                                <td>
                                    <div class="small fw-medium">{{ $subject->subject_name }}</div>
                                    <div class="text-muted" style="font-size: .75rem;">
                                        {{ $subject->department->department_code }} &middot;
                                        {{ $subject->units }} unit(s)
                                    </div>
                                </td>
                                <td class="text-center">{{ $subject->classes_count }}</td>
                                <td class="text-end no-print">
                                    @if ($instructor->status === 'Active')
                                        <a href="{{ route('admin.classes.create', ['subject_id' => $subject->id, 'instructor_id' => $instructor->id]) }}"
                                           class="btn btn-sm btn-outline-secondary" title="Create a class">
                                            <i class="bi bi-easel"></i>
                                        </a>
                                    @endif
                                    <form method="POST"
                                          action="{{ route('admin.instructors.subjects.destroy', [$instructor, $subject]) }}"
                                          class="d-inline"
                                          data-confirm="Remove {{ $subject->subject_code }} from {{ $instructor->full_name }}?">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">
                                            <i class="bi bi-x-lg"></i>
                                        </button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="4" class="text-center text-muted small py-4">
                                    No subjects assigned yet.
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    {{-- ==================== ADD SUBJECTS ==================== --}}
    <div class="col-lg-5">
        <div class="card h-100 no-print">
            <div class="card-header bg-white fw-medium">Add Subjects</div>

            <div class="card-body">

                {{-- Filter the available list --}}
                <form method="GET" action="{{ route('admin.instructors.subjects', $instructor) }}" class="row g-2 mb-3">
                    <div class="col-7">
                        <input type="text" name="available_search" class="form-control form-control-sm"
                               value="{{ $availableSearch }}" placeholder="Search subjects...">
                    </div>
                    <div class="col-5">
                        <select name="available_department" class="form-select form-select-sm">
                            <option value="">All Depts</option>
                            @foreach ($departments as $department)
                                <option value="{{ $department->id }}"
                                    {{ $availableDept == $department->id ? 'selected' : '' }}>
                                    {{ $department->department_code }}
                                </option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-12">
                        <button type="submit" class="btn btn-sm btn-secondary w-100">Filter Available</button>
                    </div>
                </form>

                {{-- Assignment form --}}
                <form method="POST" action="{{ route('admin.instructors.subjects.store', $instructor) }}">
                    @csrf

                    @if ($available->isEmpty())
                        <p class="text-muted small text-center py-4 mb-0">
                            No available subjects match your filter.
                        </p>
                    @else
                        <div class="form-check mb-2 pb-2 border-bottom">
                            <input class="form-check-input" type="checkbox" id="checkAll">
                            <label class="form-check-label small fw-medium" for="checkAll">
                                Select all ({{ $available->count() }})
                            </label>
                        </div>

                        <div style="max-height: 320px; overflow-y: auto;">
                            @foreach ($available as $subject)
                                <div class="form-check">
                                    <input class="form-check-input subject-check" type="checkbox"
                                           name="subject_ids[]" value="{{ $subject->id }}"
                                           id="subject{{ $subject->id }}">
                                    <label class="form-check-label small" for="subject{{ $subject->id }}">
                                        {{ $subject->subject_code }} &mdash; {{ $subject->subject_name }}
                                        <span class="text-muted d-block" style="font-size: .75rem;">
                                            {{ $subject->department->department_code }} &middot;
                                            {{ $subject->units }} unit(s)
                                        </span>
                                    </label>
                                </div>
                            @endforeach
                        </div>

                        <button type="submit" class="btn btn-brand w-100 mt-3">
                            <i class="bi bi-journal-plus me-1"></i>Assign Selected Subjects
                        </button>
                    @endif
                </form>

            </div>
        </div>
    </div>

</div>

@endsection

@push('scripts')
<script>
    document.getElementById('checkAll')?.addEventListener('change', function () {
        document.querySelectorAll('.subject-check').forEach(box => {
            box.checked = this.checked;
        });
    });
</script>
@endpush
