@extends('layouts.auth')

@section('title', 'Forgot Password')
@section('subtitle', 'Change your password by email')

@section('content')

    <p class="small text-muted mb-3">
        Enter the email address linked to your student or instructor account.
        We will send you a link to change your password.
    </p>

    <form method="POST" action="{{ route('password.email') }}" novalidate>
        @csrf

        <div class="mb-3">
            <label for="email" class="form-label">Email address</label>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-envelope"></i></span>
                <input type="email"
                       class="form-control @error('email') is-invalid @enderror"
                       id="email"
                       name="email"
                       value="{{ old('email') }}"
                       placeholder="you@example.com"
                       required
                       autofocus>
                @error('email')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <button type="submit" class="btn btn-brand w-100 py-2">
            <i class="bi bi-send me-1"></i> Send Change Password Link
        </button>
    </form>

    <p class="text-center small mt-3 mb-0">
        <a href="{{ route('login') }}" class="text-decoration-none fw-medium">
            <i class="bi bi-arrow-left me-1"></i>Back to login
        </a>
    </p>

@endsection
