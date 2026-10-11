<div class="row g-3">

    <div class="col-12">
        <h2 class="h6 text-uppercase text-muted mb-0">Personal Information</h2>
    </div>

    <div class="col-md-4">
        <label for="first_name" class="form-label">First Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('first_name') is-invalid @enderror"
               id="first_name" name="first_name"
               value="{{ old('first_name', $student->first_name ?? '') }}" required>
        @error('first_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="middle_name" class="form-label">Middle Name</label>
        <input type="text" class="form-control @error('middle_name') is-invalid @enderror"
               id="middle_name" name="middle_name"
               value="{{ old('middle_name', $student->middle_name ?? '') }}">
        @error('middle_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('last_name') is-invalid @enderror"
               id="last_name" name="last_name"
               value="{{ old('last_name', $student->last_name ?? '') }}" required>
        @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
        <input type="email" class="form-control @error('email') is-invalid @enderror"
               id="email" name="email"
               value="{{ old('email', $student->email ?? '') }}" required>
        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="phone" class="form-label">Phone Number</label>
        <input type="text" class="form-control @error('phone') is-invalid @enderror"
               id="phone" name="phone"
               value="{{ old('phone', $student->phone ?? '') }}" placeholder="09171234567">
        @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    {{-- The photo is added once the student exists --}}
    @isset($student)
        @include('partials.profile-photo-field', ['photoUser' => $student->user])
    @endisset

    <div class="col-12 mt-4">
        <h2 class="h6 text-uppercase text-muted mb-0">Academic Information</h2>
    </div>

    <div class="col-md-6">
        <label for="student_number" class="form-label">Student Number <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('student_number') is-invalid @enderror"
               id="student_number" name="student_number"
               value="{{ old('student_number', $student->student_number ?? '') }}"
               placeholder="2026-00016" required>
        @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
        <select class="form-select @error('department_id') is-invalid @enderror"
                id="department_id" name="department_id" required>
            <option value="">-- Select Department --</option>
            @foreach ($departments as $department)
                <option value="{{ $department->id }}"
                    {{ old('department_id', $student->department_id ?? '') == $department->id ? 'selected' : '' }}>
                    {{ $department->department_code }} &mdash; {{ $department->department_name }}
                </option>
            @endforeach
        </select>
        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="year_level" class="form-label">Year Level <span class="text-danger">*</span></label>
        <select class="form-select @error('year_level') is-invalid @enderror"
                id="year_level" name="year_level" required>
            <option value="">-- Select --</option>
            @foreach (['1st Year', '2nd Year', '3rd Year', '4th Year'] as $level)
                <option value="{{ $level }}"
                    {{ old('year_level', $student->year_level ?? '') === $level ? 'selected' : '' }}>
                    {{ $level }}
                </option>
            @endforeach
        </select>
        @error('year_level')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="section" class="form-label">Section <span class="text-danger">*</span></label>
        <input type="text" class="form-control @error('section') is-invalid @enderror"
               id="section" name="section"
               value="{{ old('section', $student->section ?? '') }}" placeholder="A" required>
        @error('section')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-4">
        <label for="status" class="form-label">Status <span class="text-danger">*</span></label>
        <select class="form-select @error('status') is-invalid @enderror"
                id="status" name="status" required>
            @foreach (\App\Models\Student::STATUSES as $state)
                <option value="{{ $state }}"
                    {{ old('status', $student->status ?? 'Active') === $state ? 'selected' : '' }}>
                    {{ $state }}
                </option>
            @endforeach
        </select>
        @error('status')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-12 mt-4">
        <h2 class="h6 text-uppercase text-muted mb-0">Login Credentials</h2>
        @isset($student)
            <p class="small text-muted mb-0">Leave both password fields blank to keep the current password.</p>
        @endisset
    </div>

    <div class="col-md-6">
        <label for="password" class="form-label">
            Password @empty($student)<span class="text-danger">*</span>@endempty
        </label>
        <input type="password" class="form-control @error('password') is-invalid @enderror"
               id="password" name="password" autocomplete="new-password">
        <div class="form-text">At least 8 characters, with letters and numbers.</div>
        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
    </div>

    <div class="col-md-6">
        <label for="password_confirmation" class="form-label">
            Confirm Password @empty($student)<span class="text-danger">*</span>@endempty
        </label>
        <input type="password" class="form-control"
               id="password_confirmation" name="password_confirmation" autocomplete="new-password">
    </div>

    <div class="col-12 d-flex gap-2 mt-4">
        <a href="{{ route('admin.students.index') }}" class="btn btn-outline-secondary">Cancel</a>
        <button type="submit" class="btn btn-brand">
            <i class="bi bi-save me-1"></i>{{ $submitLabel }}
        </button>
    </div>

</div>
