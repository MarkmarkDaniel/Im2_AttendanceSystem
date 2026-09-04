@extends('layouts.auth')

@section('title', 'Login')
@section('subtitle', 'Sign in to your account')

@section('content')

    <form method="POST" action="{{ route('login.attempt') }}" novalidate>
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

        <div class="mb-3">
            <div class="d-flex justify-content-between">
                <label for="password" class="form-label">Password</label>
                <a href="{{ route('password.request') }}" class="small text-decoration-none">Forgot password?</a>
            </div>
            <div class="input-group">
                <span class="input-group-text"><i class="bi bi-lock"></i></span>
                <input type="password"
                       class="form-control @error('password') is-invalid @enderror"
                       id="password"
                       name="password"
                       placeholder="Enter your password"
                       required>
                <button class="btn btn-outline-secondary" type="button" id="togglePassword">
                    <i class="bi bi-eye" id="toggleIcon"></i>
                </button>
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <div class="form-check mb-3">
            <input class="form-check-input" type="checkbox" name="remember" id="remember"
                   {{ old('remember') ? 'checked' : '' }}>
            <label class="form-check-label small" for="remember">Remember me on this device</label>
        </div>

        <button type="submit" class="btn btn-brand w-100 py-2">
            <i class="bi bi-box-arrow-in-right me-1"></i> Sign In
        </button>
    </form>

    <hr class="my-4">

    <p class="text-center small mb-3">
        No account yet?
        <a href="{{ route('register') }}" class="text-decoration-none fw-medium">Register as a student</a>
    </p>

    {{-- Development helper. Remove this block before real deployment. --}}
    <div class="border rounded p-3 bg-light">
        <p class="small fw-medium mb-2">
            <i class="bi bi-info-circle me-1"></i>Demo accounts (development only)
        </p>
        <table class="table table-sm small mb-0">
            <tbody>
                <tr>
                    <td class="ps-0"><span class="badge bg-dark">Admin</span></td>
                    <td>admin@example.com</td>
                    <td class="text-muted pe-0">password</td>
                </tr>
                <tr>
                    <td class="ps-0"><span class="badge bg-primary">Instructor</span></td>
                    <td>instructor@example.com</td>
                    <td class="text-muted pe-0">password</td>
                </tr>
                <tr>
                    <td class="ps-0"><span class="badge bg-success">Student</span></td>
                    <td>student@example.com</td>
                    <td class="text-muted pe-0">password</td>
                </tr>
            </tbody>
        </table>
    </div>

@endsection

@push('scripts')
<script>
    document.getElementById('togglePassword').addEventListener('click', function () {
        const field = document.getElementById('password');
        const icon  = document.getElementById('toggleIcon');
        const show  = field.type === 'password';

        field.type = show ? 'text' : 'password';
        icon.className = show ? 'bi bi-eye-slash' : 'bi bi-eye';
    });
</script>
@endpush
