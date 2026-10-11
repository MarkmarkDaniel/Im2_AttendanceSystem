@extends('layouts.app')

@section('title', 'Instructors')
@section('page-title', 'Instructors')
@section('page-subtitle', 'Manage instructor records')

@section('page-actions')
    <a href="{{ route('admin.instructors.create') }}" class="btn btn-brand btn-sm">
        <i class="bi bi-person-plus me-1"></i>Add Instructor
    </a>
@endsection

@section('content')

<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.instructors.index') }}" class="row g-2">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Name or employee no.">
                </div>
            </div>
            <div class="col-md-2">
                <select name="department_id" class="form-select form-select-sm">
                    <option value="">All Departments</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department->id }}"
                            {{ $departmentId == $department->id ? 'selected' : '' }}>
                            {{ $department->department_code }}
                        </option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-2">
                <select name="status" class="form-select form-select-sm">
                    <option value="">All Statuses</option>
                    <option value="Active"   {{ $status === 'Active'   ? 'selected' : '' }}>Active</option>
                    <option value="Inactive" {{ $status === 'Inactive' ? 'selected' : '' }}>Inactive</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                @if ($search || $departmentId || $status)
                    <a href="{{ route('admin.instructors.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
                @endif
            </div>
        </form>
    </div>

    <div class="table-responsive">
        <table class="table table-hover mb-0">
            <thead class="table-light">
                <tr>
                    <th>Employee No.</th>
                    <th>Name</th>
                    <th>Department</th>
                    <th class="text-center">Classes</th>
                    <th>Status</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($instructors as $instructor)
                    <tr>
                        <td class="small">{{ $instructor->employee_number }}</td>
                        <td>
                            <div class="d-flex align-items-center gap-2">
                                @if ($instructor->user->profileImageUrl())
                                    <img src="{{ $instructor->user->profileImageUrl() }}" alt=""
                                         class="rounded-circle border flex-shrink-0"
                                         style="width: 34px; height: 34px; object-fit: cover;">
                                @else
                                    <div class="rounded-circle border bg-light d-flex align-items-center justify-content-center text-muted flex-shrink-0"
                                         style="width: 34px; height: 34px;">
                                        <i class="bi bi-person"></i>
                                    </div>
                                @endif
                                <div>
                                    <div class="fw-medium small">{{ $instructor->full_name }}</div>
                                    <div class="text-muted" style="font-size: .78rem;">{{ $instructor->email }}</div>
                                </div>
                            </div>
                        </td>
                        <td class="small">{{ $instructor->department->department_code }}</td>
                        <td class="text-center">{{ $instructor->classes_count }}</td>
                        <td>
                            <span class="badge bg-{{ $instructor->status === 'Active' ? 'success' : 'secondary' }}">
                                {{ $instructor->status }}
                            </span>
                        </td>
                        <td class="text-end no-print">
                            <a href="{{ route('admin.instructors.edit', $instructor) }}"
                               class="btn btn-sm btn-outline-primary">
                                <i class="bi bi-pencil"></i>
                            </a>
                            <form method="POST" action="{{ route('admin.instructors.destroy', $instructor) }}"
                                  class="d-inline"
                                  data-confirm="Delete {{ $instructor->employee_number }}? This also deletes their login account.">
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
                        <td colspan="6" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>No instructors found.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($instructors->hasPages())
        <div class="card-footer bg-white no-print">{{ $instructors->links() }}</div>
    @endif

</div>

@endsection
