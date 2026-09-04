@extends('layouts.app')

@section('title', 'My Profile')
@section('page-title', 'My Profile')
@section('page-subtitle', ucfirst($user->role) . ' account')

@section('content')

<div class="row g-3">

    {{-- ==================== ACCOUNT INFO ==================== --}}
    <div class="col-lg-7">
        <div class="card">
            <div class="card-header bg-white fw-medium">Account Information</div>
            <div class="card-body">
                <table class="table table-sm mb-0">
                    <tbody>
                        <tr>
                            <th class="ps-0 text-muted small" style="width: 170px;">Full Name</th>
                            <td class="small">{{ $user->name }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted small">Email Address</th>
                            <td class="small">{{ $user->email }}</td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted small">Role</th>
                            <td><span class="badge bg-dark">{{ ucfirst($user->role) }}</span></td>
                        </tr>
                        <tr>
                            <th class="ps-0 text-muted small">Member Since</th>
                            <td class="small">{{ $user->created_at->format('F j, Y') }}</td>
                        </tr>

                        @if ($user->isStudent() && $user->student)
                            <tr><td colspan="2" class="ps-0 pt-3"><strong class="small">Student Details</strong></td></tr>
                            <tr>
                                <th class="ps-0 text-muted small">Student Number</th>
                                <td class="small">{{ $user->student->student_number }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Department</th>
                                <td class="small">
                                    {{ $user->student->department->department_code }} &mdash;
                                    {{ $user->student->department->department_name }}
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Year Level / Section</th>
                                <td class="small">{{ $user->student->year_level }} &mdash; {{ $user->student->section }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Phone</th>
                                <td class="small">{{ $user->student->phone ?? '—' }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Status</th>
                                <td>
                                    <span class="badge bg-{{ $user->student->status === 'Active' ? 'success' : 'secondary' }}">
                                        {{ $user->student->status }}
                                    </span>
                                </td>
                            </tr>
                        @endif

                        @if ($user->isInstructor() && $user->instructor)
                            <tr><td colspan="2" class="ps-0 pt-3"><strong class="small">Instructor Details</strong></td></tr>
                            <tr>
                                <th class="ps-0 text-muted small">Employee Number</th>
                                <td class="small">{{ $user->instructor->employee_number }}</td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Department</th>
                                <td class="small">
                                    {{ $user->instructor->department->department_code }} &mdash;
                                    {{ $user->instructor->department->department_name }}
                                </td>
                            </tr>
                            <tr>
                                <th class="ps-0 text-muted small">Status</th>
                                <td>
                                    <span class="badge bg-{{ $user->instructor->status === 'Active' ? 'success' : 'secondary' }}">
                                        {{ $user->instructor->status }}
                                    </span>
                                </td>
                            </tr>
                        @endif
                    </tbody>
                </table>

                <p class="small text-muted mt-3 mb-0">
                    <i class="bi bi-info-circle me-1"></i>
                    To change your personal details, contact the administrator.
                </p>
            </div>
        </div>
    </div>

    {{-- ==================== RIGHT COLUMN ==================== --}}
    <div class="col-lg-5">

        @if ($summary)
            <div class="card mb-3">
                <div class="card-header bg-white fw-medium">My Attendance Summary</div>
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <span class="small text-muted">Overall Rate</span>
                        <span class="badge bg-{{ $summary['rate'] >= config('attendance.minimum_rate', 75) ? 'success' : 'danger' }} fs-6">
                            {{ $summary['rate'] }}%
                        </span>
                    </div>
                    <table class="table table-sm small mb-0">
                        <tbody>
                            <tr>
                                <td class="ps-0 text-muted">Total Sessions</td>
                                <td class="text-end fw-medium">{{ $summary['total'] }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-success">Present</td>
                                <td class="text-end fw-medium">{{ $summary['present'] }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-warning">Late</td>
                                <td class="text-end fw-medium">{{ $summary['late'] }}</td>
                            </tr>
                            <tr>
                                <td class="ps-0 text-danger">Absent</td>
                                <td class="text-end fw-medium">{{ $summary['absent'] }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        @endif

        <div class="card" id="change-password">
            <div class="card-header bg-white fw-medium">Change Password</div>
            <div class="card-body">

                @if (! $user->isAdmin())

                    {{-- Students and instructors change their password through a
                         one-time link sent to the email on their account. --}}
                    <p class="small text-muted">
                        For your security, your password is changed through a link sent
                        to your registered email address:
                    </p>
                    <p class="small fw-medium mb-3">
                        <i class="bi bi-envelope me-1"></i>{{ $user->email }}
                    </p>

                    <form method="POST" action="{{ route('password.email.self') }}"
                          data-confirm="A change password link will be sent to {{ $user->email }}. The link is valid for 1 hour and can only be used once."
                          data-confirm-title="Change Password"
                          data-confirm-style="btn-brand">
                        @csrf
                        <button type="submit" class="btn btn-brand btn-sm w-100">
                            <i class="bi bi-send me-1"></i>Send Change Password Link
                        </button>
                    </form>

                    <div class="form-text mt-2">
                        The link expires in 1 hour and stops working once your password
                        has been changed.
                    </div>

                @else

                <form method="POST" action="{{ route('profile.password') }}" novalidate>
                    @csrf
                    @method('PATCH')

                    <div class="mb-3">
                        <label for="current_password" class="form-label small">
                            Current Password <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               class="form-control form-control-sm @error('current_password') is-invalid @enderror"
                               id="current_password" name="current_password" required>
                        @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password" class="form-label small">
                            New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password"
                               class="form-control form-control-sm @error('password') is-invalid @enderror"
                               id="password" name="password" required>
                        <div class="form-text">At least 8 characters, with letters and numbers.</div>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>

                    <div class="mb-3">
                        <label for="password_confirmation" class="form-label small">
                            Confirm New Password <span class="text-danger">*</span>
                        </label>
                        <input type="password" class="form-control form-control-sm"
                               id="password_confirmation" name="password_confirmation" required>
                    </div>

                    <button type="submit" class="btn btn-brand btn-sm w-100">
                        <i class="bi bi-shield-lock me-1"></i>Update Password
                    </button>
                </form>

                @endif

            </div>
        </div>

    </div>
</div>

@endsection
