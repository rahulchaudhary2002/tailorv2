@php
    $user = auth()->user();
    $initials = collect(explode(' ', $user->name))
        ->filter()
        ->map(fn($part) => strtoupper(substr($part, 0, 1)))
        ->take(2)
        ->implode('');
    $roleName = $user->roles()
        ->wherePivot('outlet_id', $user->current_outlet_id)
        ->value('name') ?? 'No Role';
    $unreadNotifications = $user->unreadNotifications()->latest()->limit(8)->get();
    $recentNotifications = $unreadNotifications->isNotEmpty()
        ? $unreadNotifications
        : $user->notifications()->latest()->limit(8)->get();
    $unreadNotificationCount = $user->unreadNotifications()->count();
@endphp

<header class="dashboard-header {{ request()->routeIs('dashboard') ? 'dashboard-header--dashboard' : '' }}">
    <div class="header-container">
        <div class="header-brand">
            <button
                type="button"
                class="mobile-menu-btn {{ request()->routeIs('dashboard') ? 'mobile-menu-btn--dashboard' : '' }}"
                id="sidebarToggle"
                aria-label="Open navigation"
                aria-controls="sidebar"
                aria-expanded="false"
            >
                <i class="fas fa-bars"></i>
            </button>

            <!-- Logo -->
            <a href="{{ route('dashboard') }}" class="logo">
                <img src="{{ asset('assets/images/logo.png') }}" alt="Suit Land" width="200">
            </a>
        </div>

        <!-- Header Controls -->
        <div class="header-controls">
            <!-- Search Box -->
            <form action="{{ route('search.index') }}" method="GET" class="search-box">
                <i class="fas fa-search"></i>
                <input
                    type="text"
                    name="q"
                    value="{{ request()->routeIs('search.index') ? request('q', '') : '' }}"
                    placeholder="Search orders, customers, products..."
                >
            </form>

            @if ($user->hasPermission('create-orders') || $user->hasPermission('manage-orders'))
                <a href="{{ route('order.create') }}" class="btn btn-primary">
                    <i class="fas fa-cash-register"></i>
                    <span>POS</span>
                </a>
            @endif

            <!-- Notifications -->
            <div class="notifications">
                <button type="button" class="notification-btn" id="notificationToggle" aria-label="Open notifications" title="Notifications">
                    <i class="fas fa-bell"></i>
                    @if ($unreadNotificationCount > 0)
                        <span class="notification-count">{{ $unreadNotificationCount > 99 ? '99+' : $unreadNotificationCount }}</span>
                    @endif
                </button>
                <div class="notification-dropdown" id="notificationDropdown">
                    <div class="notification-dropdown-head">
                        <div class="notification-dropdown-title">Notifications</div>
                        <div class="notification-dropdown-actions">
                            @if ($unreadNotificationCount > 0)
                                <form action="{{ route('notifications.readAll') }}" method="POST">
                                    @csrf
                                    <button type="submit" class="notification-link-btn">Mark all read</button>
                                </form>
                            @endif
                        </div>
                    </div>
                    <div class="notification-list">
                        @forelse ($recentNotifications as $notification)
                            @php
                                $notificationData = (array) $notification->data;
                                $isUnread = $notification->read_at === null;
                            @endphp
                            <a
                                href="{{ route('notifications.read', $notification->id) }}"
                                class="notification-item {{ $isUnread ? 'is-unread' : '' }}"
                            >
                                <div class="notification-item__top">
                                    <div class="notification-item__title-wrap">
                                        @if (!empty($notificationData['module']))
                                            <span class="notification-item__badge">{{ $notificationData['module'] }}</span>
                                        @endif
                                        <div class="notification-item__title">{{ $notificationData['title'] ?? 'Notification' }}</div>
                                    </div>
                                    @if ($isUnread)
                                        <span class="notification-item__dot" aria-hidden="true"></span>
                                    @endif
                                </div>
                                <div class="notification-item__body">{{ $notificationData['message'] ?? '-' }}</div>
                                <div class="notification-item__meta">
                                    <span>{{ $notification->created_at?->diffForHumans() }}</span>
                                </div>
                            </a>
                        @empty
                            <div class="notification-empty">
                                <div class="notification-empty__title">No notifications</div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-avatar">
                    @if ($user->avatar)
                        <img src="{{ \Illuminate\Support\Facades\Storage::url($user->avatar) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
                    @else
                        {{ $initials }}
                    @endif
                </div>
                <div class="user-info">
                    <div class="user-name">{{ $user->name }}</div>
                    <div class="user-role">{{ $roleName }}</div>
                </div>
                <i class="fas fa-chevron-down"></i>

                <!-- User Dropdown -->
                <div class="user-dropdown">
                    <a href="{{ route('profile.edit') }}" class="dropdown-item">
                        <i class="fas fa-user"></i>
                        <span>My Profile</span>
                    </a>
                    <div class="dropdown-divider"></div>
                    <a href="#" class="dropdown-item logout-btn" onclick="event.preventDefault(); document.getElementById('logout-form').submit();">
                        <i class="fas fa-sign-out-alt"></i>
                        <span>Logout</span>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" class="d-none">
                            @csrf
                        </form>
                    </a>
                </div>
            </div>
        </div>
    </div>
</header>
