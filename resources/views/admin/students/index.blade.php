@extends('layouts.app')

@section('title', 'Students')
@section('page-title', 'Students')
@section('page-subtitle', 'Manage student records')

@section('page-actions')
    <a href="{{ route('admin.students.create') }}" class="btn btn-brand btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add Student
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.students.index') }}" class="row g-2">
            <div class="col-md-3">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Name or student no.">
                </div>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ ($filters['department_id'] ?? '') == $department->id ? 'selected' : '' }}>
                            {{ $department->department_code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="year_level" class="form-select form-select-sm">
                    <option value="">All Year Levels</option>
                    @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $level)
                        <option value="{{ $level }}"
                            {{ ($filters['year_level'] ?? '') === $level ? 'selected' : '' }}>
                            {{ $level }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    @foreach (\App\Models\Student::STATUSES as $state)
                        <option value="{{ $state }}"
                            {{ ($filters['status'] ?? '') === $state ? 'selected' : '' }}>
                            {{ $state }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                @if ($search || array_filter($filters))
                    <a href="{{ route('admin.students.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Student No.</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th>Year / Section</th>
                    <th class="text-center">Classes</th>
                    <th>Status</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($students as $student)
                    <tr>
                        <td class="small">{{ $student->student_number }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($student->user->profileImageUrl())
                                    <img src="{{ $student->user->profileImageUrl() }}" alt=""
                                         class="rounded-circle border flex-shrink-0"
                                         style="width: 34px; height: 34px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                         style="width: 34px; height: 34px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-medium small">{{ $student->formal_name }}</div>
                                    <div class="text-muted" style="font-size: .78rem;">{{ $student->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $student->department->department_code }}</td>
                        <td class="small">{{ $student->year_level }} &mdash; {{ $student->section }}</td>
                        <td class="text-center">{{ $student->enrollments_count }}</td>
                        <td>
                            <span class="badge bg-{{ match ($student->status) {
                                'Active'   => 'success',
                                'Inactive' => 'secondary',
                                'Unofficially Dropped' => 'warning text-dark',
                                default    => 'danger',
                            } }}">{{ $student->status }}</span>
                        </td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.students.edit', $student) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.students.destroy', $student) }}"
                                  class="d-inline"
                                  data-confirm="Delete {{ $student->student_number }}? This also deletes their login account.">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm btn-outline-danger">
                                    <i class="bi bi-trash"></i>
                                </button>
                            </form>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>No students found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Showing {{ $students->firstItem() ?? 0 }}&ndash;{{ $students->lastItem() ?? 0 }}
            of {{ $students->total() }} students
        </span>
        <div class="no-print">{{ $students->links() }}</div>
    </div>

</div>

@endsection
