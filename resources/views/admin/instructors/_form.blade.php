<div class="row g-3">

    <div class="col-12">
        <h2 class="h6 text-uppercase text-muted mb-0">Personal Information</h2>
    </div>

    <div class="col-md-4">
        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
               id="first_name" name="first_name"
               value="{{ old('first_name', $instructor->first_name ?? '') }}" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="middle_name" class="form-label">Middle Name</label>
        <input type="text" class="form-control @error('middle_name') is-invalid @enderror"
               id="middle_name" name="middle_name"
               value="{{ old('middle_name', $instructor->middle_name ?? '') }}">
        @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
               id="last_name" name="last_name"
               value="{{ old('last_name', $instructor->last_name ?? '') }}" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- The photo is added once the instructor exists --}}
    @isset($instructor)
        @include('partials.profile-photo-field', ['photoUser' => $instructor->user])
    @endisset

    <div class="col-12 mt-4">
        <h2 class="h6 text-uppercase text-muted mb-0">Employment Information</h2>
    </div>

    <div class="col-md-4">
        <label for="employee_number" class="form-label">Employee Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('employee_number') is-invalid @enderror"
               id="employee_number" name="employee_number"
               value="{{ old('employee_number', $instructor->employee_number ?? '') }}"
               placeholder="EMP-2026-005" required>
        @error('employee_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-5">
        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
        <select class="form-select @error('department_id') is-invalid @enderror"
                id="department_id" name="department_id" required>
            <option value="">-- Select Department --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    {{ old('department_id', $instructor->department_id ?? '') == $department->id ? 'selected' : '' }}>
                    {{ $department->department_code }} &mdash; {{ $department->department_name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-3">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror"
                id="status" name="status" required>
            @foreach (['Active', 'Inactive'] as $state)
                <option value="{{ $state }}"
                    {{ old('status', $instructor->status ?? 'Active') === $state ? 'selected' : '' }}>
                    {{ $state }}
                </option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mt-4">
        <h2 class="h6 text-uppercase text-muted mb-0">Login Credentials</h2>
        @isset($instructor)
            <p class="small text-muted mb-0">Leave both password fields blank to keep the current password.</p>
        @endisset
    </div>

    <div class="col-md-4">
        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email"
               value="{{ old('email', $instructor->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="password" class="form-label">
            Password @empty($instructor)<span class="text-danger">*</span>@endempty
        </label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" autocomplete="new-password">
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="password_confirmation" class="form-label">
            Confirm Password @empty($instructor)<span class="text-danger">*</span>@endempty
        </label>
        <input type="password" class="form-control"
               id="password_confirmation" name="password_confirmation" autocomplete="new-password">
    </div>

    <div class="col-12 d-flex gap-2 mt-4">
        <a href="{{ route('admin.instructors.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-brand">
            <i class="bi bi-save me-1"></i>{{ $submitLabel }}
        </button>
    </div>

</div>
