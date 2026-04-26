<aside class="sidebar" id="sidebar">
    @php
        $authUser = auth()->user();
        $currentOutletId = (int) ($authUser?->current_outlet_id ?? 0);
        $hasWorkerRole = (bool) $authUser?->roles()
            ->where('name', 'Worker')
            ->when($currentOutletId > 0, fn ($query) => $query->where('user_role.outlet_id', $currentOutletId))
            ->exists();
    @endphp
    <div class="sidebar-header">
        <!-- Outlet Selector -->
        <div class="outlet-selector-wrapper">
            <div class="outlet-selector" id="outletToggle">
                <i class="fas fa-store"></i>
                <div class="outlet-info">
                    <div class="outlet-name">{{ $authUser?->currentOutlet?->name }}</div>
                    <div class="outlet-location">{{ $authUser?->currentOutlet?->address }}</div>
                </div>
                <i class="fas fa-chevron-down"></i>
            </div>

            <div class="outlet-dropdown" id="outletDropdown">
                <ul class="outlet-list">
                    @foreach($authUser?->outlets ?? [] as $outlet)
                    <li class="outlet-item {{ $authUser?->current_outlet_id === $outlet->id ? 'active' : '' }}">
                        <form action="{{ route('outlet.switch') }}" method="POST" class="outlet-switch-form">
                            @csrf
                            <input type="hidden" name="outlet_id" value="{{ $outlet->id }}">
                            <button type="submit" class="outlet-switch-button">
                                <span class="outlet-item-name">{{ $outlet->name }}</span>
                                <span class="outlet-item-location">{{ $outlet->address }}</span>
                            </button>
                        </form>
                    </li>
                    @endforeach
                </ul>
            </div>
        </div>
    </div>

    <!-- Navigation -->
    <nav class="sidebar-nav">
        <div class="nav-title">Main Navigation</div>
        <ul class="nav-menu">
            @can('view-dashboard')
                <li class="nav-item">
                    <a href="{{ route('dashboard') }}" class="nav-link {{ request()->routeIs('dashboard') ? 'active' : '' }}">
                        <i class="fas fa-th-large nav-icon"></i>
                        <span class="nav-text">Dashboard</span>
                    </a>
                </li>
            @endcan
            @canany(['view-outlets', 'manage-outlets'])
                <li class="nav-item">
                    <a href="{{ route('outlet.index') }}" class="nav-link {{ request()->routeIs('outlet.*') ? 'active' : '' }}">
                        <i class="fas fa-store nav-icon"></i>
                        <span class="nav-text">Outlet Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-roles', 'manage-roles'])
                <li class="nav-item">
                    <a href="{{ route('role.index') }}" class="nav-link {{ request()->routeIs('role.*') ? 'active' : '' }}">
                        <i class="fas fa-user-shield nav-icon"></i>
                        <span class="nav-text">Role Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-users', 'manage-users'])
                <li class="nav-item">
                    <a href="{{ route('user.index') }}" class="nav-link {{ request()->routeIs('user.*') ? 'active' : '' }}">
                        <i class="fas fa-users nav-icon"></i>
                        <span class="nav-text">User Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-users', 'manage-users'])
                <li class="nav-item">
                    <a href="{{ route('worker.index') }}" class="nav-link {{ request()->routeIs('worker.*') ? 'active' : '' }}">
                        <i class="fas fa-user-gear nav-icon"></i>
                        <span class="nav-text">Worker Management</span>
                    </a>
                </li>
            @endcanany
        </ul>

        <div class="nav-title">Business Management</div>
        <ul class="nav-menu">
            @canany(['view-customers', 'manage-customers'])
                <li class="nav-item">
                    <a href="{{ route('customer.index') }}" class="nav-link {{ request()->routeIs('customer.*') ? 'active' : '' }}">
                        <i class="fas fa-user-friends nav-icon"></i>
                        <span class="nav-text">Customer</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-products', 'manage-products'])
                <li class="nav-item">
                    <a href="{{ route('product.index') }}" class="nav-link {{ request()->routeIs('product.*') ? 'active' : '' }}">
                        <i class="fas fa-box-open nav-icon"></i>
                        <span class="nav-text">Product</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-vendors', 'manage-vendors'])
                <li class="nav-item">
                    <a href="{{ route('vendor.index') }}" class="nav-link {{ request()->routeIs('vendor.*') ? 'active' : '' }}">
                        <i class="fas fa-truck-loading nav-icon"></i>
                        <span class="nav-text">Vendor Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-raw-material-purchases', 'manage-raw-material-purchases'])
                <li class="nav-item">
                    <a href="{{ route('rawMaterialPurchase.index') }}" class="nav-link {{ request()->routeIs('rawMaterialPurchase.*') ? 'active' : '' }}">
                        <i class="fas fa-cart-plus nav-icon"></i>
                        <span class="nav-text">Purchase</span>
                    </a>
                </li>
            @endcanany
            {{--@canany(['view-manufacture-unit', 'manage-manufacture-unit'])
                <li class="nav-item">
                    <a href="{{ route('manufactureUnit.index') }}" class="nav-link {{ request()->routeIs('manufactureUnit.*') ? 'active' : '' }}">
                        <i class="fas fa-industry nav-icon"></i>
                        <span class="nav-text">Manufacture Unit</span>
                    </a>
                </li>
            @endcanany--}}
            @canany(['view-inventory', 'manage-inventory'])
                <li class="nav-item">
                    <a href="{{ route('inventory.index') }}" class="nav-link {{ request()->routeIs('inventory.*') ? 'active' : '' }}">
                        <i class="fas fa-warehouse nav-icon"></i>
                        <span class="nav-text">Inventory Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-orders', 'manage-orders'])
                <li class="nav-item">
                    <a href="{{ route('order.index') }}" class="nav-link {{ request()->routeIs('order.*') && !request()->routeIs('order.assignedJobs') ? 'active' : '' }}">
                        <i class="fas fa-file-invoice nav-icon"></i>
                        <span class="nav-text">Order Management</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-task-management', 'manage-task-management', 'manage-orders'])
                <li class="nav-item">
                    <a href="{{ route('taskManagement.index') }}" class="nav-link {{ request()->routeIs('taskManagement.*') ? 'active' : '' }}">
                        <i class="fas fa-list-check nav-icon"></i>
                        <span class="nav-text">Task Management</span>
                    </a>
                </li>
            @endcanany
            @if ($hasWorkerRole)
                <li class="nav-item">
                    <a href="{{ route('order.assignedJobs') }}" class="nav-link {{ request()->routeIs('order.assignedJobs') ? 'active' : '' }}">
                        <i class="fas fa-user-check nav-icon"></i>
                        <span class="nav-text">Assigned Jobs</span>
                    </a>
                </li>
            @endif
            @canany(['view-payment-management', 'manage-payment-management', 'manage-orders'])
                <li class="nav-item">
                    <a href="{{ route('paymentManagement.index') }}" class="nav-link {{ request()->routeIs('paymentManagement.*') ? 'active' : '' }}">
                        <i class="fas fa-money-check-dollar nav-icon"></i>
                        <span class="nav-text">Payment Management</span>
                    </a>
                </li>
            @endcanany
        </ul>

        <div class="nav-title">System Configuration</div>
        <ul class="nav-menu">
            @canany(['view-units', 'manage-units'])
                <li class="nav-item">
                    <a href="{{ route('unit.index') }}" class="nav-link {{ request()->routeIs('unit.*') ? 'active' : '' }}">
                        <i class="fa-solid fa-scale-balanced nav-icon"></i>
                        <span class="nav-text">Measurement Unit</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-garment-types', 'manage-garment-types'])
                <li class="nav-item">
                    <a href="{{ route('garmentType.index') }}" class="nav-link {{ request()->routeIs('garmentType.*') ? 'active' : '' }}">
                        <i class="fas fa-ruler-combined nav-icon"></i>
                        <span class="nav-text">Garment Type</span>
                    </a>
                </li>
            @endcanany
            @canany(['view-settings', 'manage-settings'])
                <li class="nav-item">
                    <a href="{{ route('setting.index') }}" class="nav-link {{ request()->routeIs('setting.*') ? 'active' : '' }}">
                        <i class="fas fa-gear nav-icon"></i>
                        <span class="nav-text">Settings</span>
                    </a>
                </li>
            @endcanany
        </ul>
    </nav>
</aside>
