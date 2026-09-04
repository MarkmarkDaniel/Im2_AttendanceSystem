@php
    $user    = auth()->user();
    $unread  = $user->unreadNotifications()->count();
    $recent  = $user->notifications()->limit(6)->get();
@endphp

<header class="topbar d-flex align-items-center justify-content-between no-print">

    <div class="d-flex align-items-center gap-3">
        <button class="btn btn-sm btn-outline-secondary d-lg-none" id="sidebarToggle" type="button">
            <i class="bi bi-list"></i>
        </button>
        <span class="text-muted small d-none d-md-inline">
            <i class="bi bi-calendar3 me-1"></i>{{ now()->format('l, F j, Y') }}
            <i class="bi bi-clock ms-3 me-1"></i><span id="liveClock"></span>
        </span>
    </div>

    <div class="d-flex align-items-center gap-2">

        {{-- ==================== NOTIFICATIONS ==================== --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-light position-relative" data-bs-toggle="dropdown">
                <i class="bi bi-bell fs-6"></i>
                @if ($unread > 0)
                    <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">
                        {{ $unread > 99 ? '99+' : $unread }}
                    </span>
                @endif
            </button>

            <div class="dropdown-menu dropdown-menu-end notif-dropdown p-0">

                {{-- Header --}}
                <div class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">
                    <span class="fw-medium small">Notifications</span>
                    <span class="badge bg-secondary">{{ $unread }} unread</span>
                </div>

                {{-- List --}}
                @forelse ($recent as $notification)
                    <div class="notif-item px-3 py-2 {{ $notification->is_read ? '' : 'unread' }}">
                        <div class="d-flex gap-2">
                            <i class="bi {{ $notification->icon }} text-{{ $notification->type }}"></i>
                            <div class="flex-grow-1">
                                <div class="small fw-medium">{{ $notification->title }}</div>
                                <div class="small text-muted">{{ Str::limit($notification->message, 70) }}</div>
                                <div class="text-muted" style="font-size: .72rem;">
                                    {{ $notification->created_at->diffForHumans() }}
                                </div>
                            </div>
                        </div>
                    </div>
                @empty
                    <div class="px-3 py-4 text-center text-muted small">
                        <i class="bi bi-inbox d-block fs-4 mb-1"></i>No notifications yet
                    </div>
                @endforelse

                {{-- Footer --}}
                <div class="px-3 py-2 border-top d-flex gap-2">
                    <a href="{{ route('notifications.index') }}"
                       class="btn btn-sm btn-outline-secondary flex-grow-1">
                        View All
                    </a>
                    @if ($unread > 0)
                        <form method="POST" action="{{ route('notifications.readAll') }}" class="flex-grow-1 mb-0">
                            @csrf
                            <button type="submit" class="btn btn-sm btn-outline-primary w-100">
                                Mark All Read
                            </button>
                        </form>
                    @endif
                </div>

            </div>
        </div>

        {{-- ==================== USER MENU ==================== --}}
        <div class="dropdown">
            <button class="btn btn-sm btn-light d-flex align-items-center gap-2" data-bs-toggle="dropdown">
                <i class="bi bi-person-circle fs-6"></i>
                <span class="small d-none d-sm-inline">{{ $user->name }}</span>
                <i class="bi bi-chevron-down small"></i>
            </button>
            <ul class="dropdown-menu dropdown-menu-end">
                <li class="px-3 py-2 border-bottom">
                    <div class="small fw-medium">{{ $user->name }}</div>
                    <div class="small text-muted">{{ $user->email }}</div>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('profile.show') }}">
                        <i class="bi bi-person me-2"></i>My Profile
                    </a>
                </li>
                <li>
                    <a class="dropdown-item" href="{{ route('notifications.index') }}">
                        <i class="bi bi-bell me-2"></i>Notifications
                        @if ($unread > 0)
                            <span class="badge bg-danger ms-1">{{ $unread }}</span>
                        @endif
                    </a>
                </li>
                <li>
                    @if ($user->isAdmin())
                        {{-- Admins still change their own password with their current one --}}
                        <a class="dropdown-item" href="{{ route('profile.show') }}#change-password">
                            <i class="bi bi-shield-lock me-2"></i>Change Password
                        </a>
                    @else
                        {{-- Students and instructors get a one-time link by email --}}
                        <form method="POST" action="{{ route('password.email.self') }}" class="mb-0"
                              data-confirm="A change password link will be sent to {{ $user->email }}. The link is valid for 1 hour and can only be used once."
                              data-confirm-title="Change Password"
                              data-confirm-style="btn-brand">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-shield-lock me-2"></i>Change Password
                            </button>
                        </form>
                    @endif
                </li>
                <li><hr class="dropdown-divider"></li>
                <li>
                    <form method="POST" action="{{ route('logout') }}">
                        @csrf
                        <button type="submit" class="dropdown-item text-danger">
                            <i class="bi bi-box-arrow-right me-2"></i>Logout
                        </button>
                    </form>
                </li>
            </ul>
        </div>

    </div>
</header>
