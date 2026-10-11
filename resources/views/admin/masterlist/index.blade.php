@extends('layouts.app')

@section('title', 'Student Masterlist')
@section('page-title', 'Student Masterlist')
@section('page-subtitle', 'Only student numbers on this list can register an account')

@section('content')

<div class="row g-3 mb-3 no-print">

    {{-- ==================== ADD ONE ==================== --}}
    <div class="col-lg-8">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Add a Student to the Masterlist</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.masterlist.store') }}" novalidate>
                    @csrf

                    <div class="row g-2">
                        <div class="col-md-4">
                            <label for="student_number" class="form-label small">
                                Student Number <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('student_number') is-invalid @enderror"
                                   id="student_number" name="student_number"
                                   value="{{ old('student_number') }}" placeholder="2026-00021" required>
                            @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="birth_date" class="form-label small">
                                Birth Date <span class="text-danger">*</span>
                            </label>
                            <input type="date"
                                   class="form-control form-control-sm @error('birth_date') is-invalid @enderror"
                                   id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required>
                            @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="department_id" class="form-label small">
                                Department <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('department_id') is-invalid @enderror"
                                    id="department_id" name="department_id" required>
                                <option value="">-- Select --</option>
                                @foreach ($departments as $department)
                                    <option value="{{ $department->id }}"
                                        {{ old('department_id') == $department->id ? 'selected' : '' }}>
                                        {{ $department->department_code }}
                                    </option>
                                @endforeach
                            </select>
                            @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="first_name" class="form-label small">
                                First Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('first_name') is-invalid @enderror"
                                   id="first_name" name="first_name" value="{{ old('first_name') }}" required>
                            @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="middle_name" class="form-label small">Middle Name</label>
                            <input type="text"
                                   class="form-control form-control-sm @error('middle_name') is-invalid @enderror"
                                   id="middle_name" name="middle_name" value="{{ old('middle_name') }}">
                            @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="last_name" class="form-label small">
                                Last Name <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('last_name') is-invalid @enderror"
                                   id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                            @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="col-md-4">
                            <label for="year_level" class="form-label small">
                                Year Level <span class="text-danger">*</span>
                            </label>
                            <select class="form-select form-select-sm @error('year_level') is-invalid @enderror"
                                    id="year_level" name="year_level" required>
                                <option value="">-- Select --</option>
                                @foreach (\App\Models\MasterlistEntry::YEAR_LEVELS as $level)
                                    <option value="{{ $level }}" {{ old('year_level') === $level ? 'selected' : '' }}>
                                        {{ $level }}
                                    </option>
                                @endforeach
                            </select>
                            @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4">
                            <label for="section" class="form-label small">
                                Section <span class="text-danger">*</span>
                            </label>
                            <input type="text"
                                   class="form-control form-control-sm @error('section') is-invalid @enderror"
                                   id="section" name="section" value="{{ old('section') }}" placeholder="A" required>
                            @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-4 d-flex align-items-end">
                            <button type="submit" class="btn btn-brand btn-sm w-100">
                                <i class="bi bi-plus-lg me-1"></i>Add to Masterlist
                            </button>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>

    {{-- ==================== IMPORT MANY ==================== --}}
    <div class="col-lg-4">
        <div class="card h-100">
            <div class="card-header bg-white fw-medium">Import from CSV</div>
            <div class="card-body">
                <form method="POST" action="{{ route('admin.masterlist.import') }}"
                      enctype="multipart/form-data" novalidate>
                    @csrf

                    <div class="mb-2">
                        <input type="file"
                               class="form-control form-control-sm @error('file') is-invalid @enderror"
                               name="file" accept=".csv" required>
                        @error('file')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <button type="submit" class="btn btn-brand btn-sm w-100">
                        <i class="bi bi-upload me-1"></i>Import
                    </button>
                </form>

                <p class="small text-muted mt-3 mb-2">
                    Columns, in this order:
                    <code>{{ implode(', ', $csvColumns) }}</code>.
                    Birth date as <code>YYYY-MM-DD</code>.
                </p>
                <a href="{{ route('admin.masterlist.template') }}" class="small text-decoration-none">
                    <i class="bi bi-download me-1"></i>Download template
                </a>
            </div>
        </div>
    </div>

</div>

{{-- ==================== LIST ==================== --}}
<div class="card">

    <div class="card-header bg-white no-print">
        <form method="GET" action="{{ route('admin.masterlist.index') }}" class="row g-2">
            <div class="col-md-4">
                <div class="input-group input-group-sm">
                    <span class="input-group-text"><i class="bi bi-search"></i></span>
                    <input type="text" name="search" class="form-control"
                           value="{{ $search }}" placeholder="Name or student no.">
                </div>
            </div>
            <div class="col-md-3">
                <select name="state" class="form-select form-select-sm">
                    <option value="">All</option>
                    <option value="registered"   {{ $state === 'registered' ? 'selected' : '' }}>Registered</option>
                    <option value="unregistered" {{ $state === 'unregistered' ? 'selected' : '' }}>Not yet registered</option>
                </select>
            </div>
            <div class="col-auto">
                <button type="submit" class="btn btn-sm btn-secondary">Filter</button>
                @if ($search || $state)
                    <a href="{{ route('admin.masterlist.index') }}" class="btn btn-sm btn-outline-secondary">Clear</a>
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
                    <th>Birth Date</th>
                    <th>Department</th>
                    <th>Year / Section</th>
                    <th>Account</th>
                    <th class="text-end no-print">Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($entries as $entry)
                    <tr>
                        <td class="small">{{ $entry->student_number }}</td>
                        <td class="fw-medium small">{{ $entry->formal_name }}</td>
                        <td class="small">{{ $entry->birth_date->format('M j, Y') }}</td>
                        <td class="small">{{ $entry->department->department_code }}</td>
                        <td class="small">{{ $entry->year_level }} &mdash; {{ $entry->section }}</td>
                        <td>
                            @if ($entry->account)
                                <span class="badge bg-success">Registered</span>
                            @else
                                <span class="badge bg-secondary">Not yet</span>
                            @endif
                        </td>
                        <td class="text-end no-print">
                            @unless ($entry->account)
                                <form method="POST" action="{{ route('admin.masterlist.destroy', $entry) }}"
                                      class="d-inline"
                                      data-confirm="Remove {{ $entry->student_number }} from the masterlist? They will not be able to register.">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </form>
                            @endunless
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted py-4">
                            <i class="bi bi-inbox d-block fs-3 mb-2"></i>
                            The masterlist is empty. Nobody can register until students are added.
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="card-footer bg-white d-flex justify-content-between align-items-center">
        <span class="small text-muted">
            Showing {{ $entries->firstItem() ?? 0 }}&ndash;{{ $entries->lastItem() ?? 0 }}
            of {{ $entries->total() }} entries
        </span>
        <div class="no-print">{{ $entries->links() }}</div>
    </div>

</div>

@endsection
