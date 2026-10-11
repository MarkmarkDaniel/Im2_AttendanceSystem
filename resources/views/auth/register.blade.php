@extends('layouts.auth')

@section('title', 'Student Registration')
@section('subtitle', 'Create your student account')
@section('column-width', 'col-md-9 col-lg-7')

@section('content')

    <form method="POST" action="{{ route('register.store') }}" novalidate>
        @csrf

        {{-- ===================== Verify Identity ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-2">
            <i class="bi bi-patch-check me-1"></i>Verify Your Identity
        </h2>
        <p class="small text-muted mb-3">
            Only enrolled students can register. Enter your details exactly as they
            appear in the school's records.
        </p>

        <div class="row g-3 mb-4">
            <div class="col-12">
                <label for="student_number" class="form-label">Student Number <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('student_number') is-invalid @enderror"
                       id="student_number" name="student_number" value="{{ old('student_number') }}"
                       placeholder="2026-00016" required autofocus>
                @error('student_number')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="last_name" class="form-label">Last Name <span class="text-danger">*</span></label>
                <input type="text"
                       class="form-control @error('last_name') is-invalid @enderror"
                       id="last_name" name="last_name" value="{{ old('last_name') }}" required>
                @error('last_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="birth_date" class="form-label">Date of Birth <span class="text-danger">*</span></label>
                <input type="date"
                       class="form-control @error('birth_date') is-invalid @enderror"
                       id="birth_date" name="birth_date" value="{{ old('birth_date') }}" required>
                @error('birth_date')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ===================== Contact Details ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-3">
            <i class="bi bi-envelope me-1"></i>Contact Details
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="email" class="form-label">Email Address <span class="text-danger">*</span></label>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email" name="email" value="{{ old('email') }}" required>
                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="phone" class="form-label">Phone Number</label>
                <input type="text"
                       class="form-control @error('phone') is-invalid @enderror"
                       id="phone" name="phone" value="{{ old('phone') }}" placeholder="09171234567">
                @error('phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>
        </div>

        {{-- ===================== Account Security ===================== --}}
        <h2 class="h6 text-uppercase text-muted mb-3">
            <i class="bi bi-shield-lock me-1"></i>Account Security
        </h2>

        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password" name="password" required>
                <div class="form-text">At least 8 characters, with letters and numbers.</div>
                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
            </div>

            <div class="col-md-6">
                <label for="password_confirmation" class="form-label">Confirm Password <span class="text-danger">*</span></label>
                <input type="password" class="form-control"
                       id="password_confirmation" name="password_confirmation" required>
            </div>
        </div>

        <div class="d-flex gap-2">
            <a href="{{ route('login') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left me-1"></i> Back to Login
            </a>
            <button type="submit" class="btn btn-brand flex-grow-1 py-2">
                <i class="bi bi-person-plus me-1"></i> Create Account
            </button>
        </div>
    </form>

@endsection
