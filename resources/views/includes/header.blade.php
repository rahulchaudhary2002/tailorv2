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
@endphp

<header class="dashboard-header">
    <div class="header-container">
        <!-- Logo -->
        <a href="{{ route('dashboard') }}" class="logo">
            <div class="logo-icon">
                <i class="fas fa-vest"></i>
            </div>
            <div class="logo-text">
                <h1>Fashion Tailor Pro</h1>
                <p>Admin Dashboard</p>
            </div>
        </a>

        <!-- Header Controls -->
        <div class="header-controls">
            <!-- Search Box -->
            <div class="search-box">
                <i class="fas fa-search"></i>
                <input type="text" placeholder="Search orders, customers, products...">
            </div>

            <!-- Notifications -->
            <div class="notifications">
                <button class="notification-btn">
                    <i class="fas fa-bell"></i>
                    <span class="notification-count">3</span>
                </button>
                <div class="notification-dropdown">
                    <div class="notification-list">
                        <!-- Notifications will be populated by JavaScript -->
                    </div>
                </div>
            </div>

            <!-- User Menu -->
            <div class="user-menu">
                <div class="user-avatar">
                    @if ($user->avatar)
                        <img src="{{ asset('storage/' . $user->avatar) }}" alt="{{ $user->name }}" style="width: 100%; height: 100%; object-fit: cover; border-radius: 50%;">
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
                    <a href="settings.html" class="dropdown-item">
                        <i class="fas fa-cog"></i>
                        <span>Settings</span>
                    </a>
                    <a href="help.html" class="dropdown-item">
                        <i class="fas fa-question-circle"></i>
                        <span>Help & Support</span>
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
