@php
    $user = auth()->user();
@endphp

<nav class="sidebar" id="sidebar">

    <div class="brand d-flex align-items-center gap-3">
        <div class="brand-emblem"><i class="bi bi-mortarboard-fill"></i></div>
        <div>
            <div class="brand-name">Attendance System</div>
            <div class="brand-role">{{ ucfirst($user->role) }} Panel</div>
        </div>
    </div>

    <ul class="nav flex-column py-2">

        {{-- ============ ADMIN ============ --}}
        @if ($user->isAdmin())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"
                   href="{{ route('admin.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>

            <li class="nav-heading">People</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.students.*') ? 'active' : '' }}"
                   href="{{ route('admin.students.index') }}">
                    <i class="bi bi-people me-2"></i>Students
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.masterlist.*') ? 'active' : '' }}"
                   href="{{ route('admin.masterlist.index') }}">
                    <i class="bi bi-card-checklist me-2"></i>Masterlist
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.instructors.*') ? 'active' : '' }}"
                   href="{{ route('admin.instructors.index') }}">
                    <i class="bi bi-person-badge me-2"></i>Instructors
                </a>
            </li>

            <li class="nav-heading">Academics</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.departments.*') ? 'active' : '' }}"
                   href="{{ route('admin.departments.index') }}">
                    <i class="bi bi-building me-2"></i>Departments
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.subjects.*') ? 'active' : '' }}"
                   href="{{ route('admin.subjects.index') }}">
                    <i class="bi bi-journal-bookmark me-2"></i>Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.classes.*') ? 'active' : '' }}"
                   href="{{ route('admin.classes.index') }}">
                    <i class="bi bi-easel me-2"></i>Classes
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.enrollments.*') ? 'active' : '' }}"
                   href="{{ route('admin.enrollments.index') }}">
                    <i class="bi bi-clipboard-check me-2"></i>Enrollments
                </a>
            </li>

             <li class="nav-heading">Reports</li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('admin.reports.*') ? 'active' : '' }}"
                   href="{{ route('admin.reports.index') }}">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reports
                </a>
            </li>
        @endif

        {{-- ============ INSTRUCTOR ============ --}}
        @if ($user->isInstructor())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('instructor.dashboard') ? 'active' : '' }}"
                   href="{{ route('instructor.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>

            <li class="nav-heading">Teaching</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('instructor.classes.*') ? 'active' : '' }}"
                   href="{{ route('instructor.classes.index') }}">
                    <i class="bi bi-easel me-2"></i>My Classes
                </a>
            </li>

            <li class="nav-heading">Attendance</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('instructor.attendance.daily') ? 'active' : '' }}"
                   href="{{ route('instructor.attendance.daily') }}">
                    <i class="bi bi-calendar-check me-2"></i>Daily Monitoring
                </a>
            </li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('instructor.qr.*') ? 'active' : '' }}"
                   href="{{ route('instructor.qr.index') }}">
                    <i class="bi bi-qr-code me-2"></i>QR Attendance
                </a>
            </li>

             <li class="nav-heading">Reports</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('instructor.reports.*') ? 'active' : '' }}"
                   href="{{ route('instructor.reports.index') }}">
                    <i class="bi bi-file-earmark-bar-graph me-2"></i>Reports
                </a>
            </li>
        @endif

        {{-- ============ STUDENT ============ --}}
      {{-- ============ STUDENT ============ --}}
        @if ($user->isStudent())
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('student.dashboard') ? 'active' : '' }}"
                   href="{{ route('student.dashboard') }}">
                    <i class="bi bi-speedometer2 me-2"></i>Dashboard
                </a>
            </li>

            <li class="nav-heading">Academics</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('student.subjects') ? 'active' : '' }}"
                   href="{{ route('student.subjects') }}">
                    <i class="bi bi-journal-bookmark me-2"></i>My Subjects
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('student.attendance') ? 'active' : '' }}"
                   href="{{ route('student.attendance') }}">
                    <i class="bi bi-list-check me-2"></i>My Attendance
                </a>
            </li>

            <li class="nav-heading">Attendance</li>

            <li class="nav-item">
                <a class="nav-link {{ request()->routeIs('student.qr.*') ? 'active' : '' }}"
                   href="{{ route('student.qr.scanner') }}">
                    <i class="bi bi-qr-code-scan me-2"></i>Scan QR Attendance
                </a>
            </li>
        @endif

        <li class="nav-heading">Account</li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('notifications.*') ? 'active' : '' }}"
               href="{{ route('notifications.index') }}">
                <i class="bi bi-bell me-2"></i>Notifications
                @php $unread = $user->unreadNotifications()->count(); @endphp
                @if ($unread > 0)
                    <span class="badge bg-danger ms-1">{{ $unread }}</span>
                @endif
            </a>
        </li>

        <li class="nav-item">
            <a class="nav-link {{ request()->routeIs('profile.*') ? 'active' : '' }}"
               href="{{ route('profile.show') }}">
                <i class="bi bi-person me-2"></i>Profile
            </a>
        </li>

        <li class="nav-item">
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link border-0 bg-transparent w-100 text-start">
                    <i class="bi bi-box-arrow-right me-2"></i>Logout
                </button>
            </form>
        </li>

    </ul>
</nav>
