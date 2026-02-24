@extends('layouts.app')

@section('title', 'Customer Management')

@section('page-specific-style')
<style>
    .customer-directory-page .page-header-actions {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 30px;
        flex-wrap: wrap;
        gap: 20px;
    }

    .customer-directory-page .action-buttons {
        display: flex;
        gap: 12px;
        flex-wrap: wrap;
    }

    .customer-directory-page .customer-stats {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(210px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .customer-directory-page .stat-card {
        background: white;
        border-radius: var(--radius-card);
        padding: 24px;
        box-shadow: var(--shadow-sm);
        text-align: center;
        border: 2px solid transparent;
        transition: all var(--transition-normal);
    }

    .customer-directory-page .stat-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-md);
        border-color: var(--primary);
    }

    .customer-directory-page .stat-icon {
        width: 58px;
        height: 58px;
        border-radius: 50%;
        margin: 0 auto 16px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: var(--text-xl);
        background: var(--primary-bg);
        color: var(--primary);
    }

    .customer-directory-page .stat-value {
        font-size: var(--text-3xl);
        font-weight: var(--font-bold);
        color: var(--dark);
        margin-bottom: 4px;
    }

    .customer-directory-page .stat-label {
        color: var(--gray-600);
        font-size: var(--text-sm);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .customer-directory-page .filter-bar {
        position: relative;
        background: linear-gradient(160deg, #fff 0%, #f9f3ec 100%);
        border-radius: var(--radius-card);
        padding: 24px;
        box-shadow: var(--shadow-md);
        margin-bottom: 24px;
        border: 1px solid #e8dccf;
        overflow: hidden;
    }

    .customer-directory-page .filter-bar::before {
        content: "";
        position: absolute;
        inset: 0 auto 0 0;
        width: 5px;
        background: linear-gradient(180deg, var(--primary), var(--secondary));
    }

    .customer-directory-page .filter-head {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        flex-wrap: wrap;
        margin-bottom: 16px;
    }

    .customer-directory-page .filter-head-meta {
        display: flex;
        flex-direction: column;
        gap: 2px;
    }

    .customer-directory-page .filter-title {
        margin: 0;
        font-size: var(--text-lg);
        font-weight: var(--font-bold);
        color: var(--dark);
    }

    .customer-directory-page .filter-subtitle {
        margin: 2px 0 0;
        color: var(--gray-600);
        font-size: var(--text-sm);
    }

    .customer-directory-page .clear-filters-btn {
        border-color: #dbc7b2;
        background: #fff;
    }

    .customer-directory-page .clear-filters-btn:hover {
        border-color: var(--primary);
        color: var(--primary);
        background: #fff;
    }

    .customer-directory-page .filter-grid {
        display: grid;
        grid-template-columns: repeat(3, minmax(0, 1fr));
        gap: 14px;
        margin-bottom: 0;
        background: white;
        border: 1px solid #eadfce;
        border-radius: var(--radius-lg);
        padding: 14px;
    }

    .customer-directory-page .filter-control {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .customer-directory-page .filter-control.search-field {
        grid-column: 1 / -1;
    }

    .customer-directory-page .filter-label {
        font-size: 12px;
        font-weight: var(--font-semibold);
        color: #6b5b4d;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        margin: 0;
    }

    .customer-directory-page .search-box-large {
        position: relative;
        grid-column: 1 / -1;
    }

    .customer-directory-page .search-box-large i {
        position: absolute;
        left: 18px;
        top: 50%;
        transform: translateY(-50%);
        color: var(--gray-500);
    }

    .customer-directory-page .search-box-large input {
        width: 100%;
        min-height: 46px;
        padding: 12px 16px 12px 44px;
        border: 2px solid var(--light-border);
        border-radius: var(--radius-md);
        font-size: var(--text-sm);
        background: #fff;
        transition: all var(--transition-fast);
    }

    .customer-directory-page .search-box-large input:focus {
        outline: none;
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.1);
    }

    .customer-directory-page .filter-select-wrap {
        position: relative;
    }

    .customer-directory-page .filter-select-wrap i {
        position: absolute;
        left: 12px;
        top: 50%;
        transform: translateY(-50%);
        color: #8a7a6c;
        font-size: 13px;
        pointer-events: none;
    }

    .customer-directory-page .filter-control .form-control {
        min-height: 46px;
        border-radius: var(--radius-md);
        border: 2px solid var(--light-border);
        background: #fff;
        transition: all var(--transition-fast);
        padding-left: 36px;
        font-size: var(--text-sm);
    }

    .customer-directory-page .filter-control .form-control:focus {
        border-color: var(--primary);
        box-shadow: 0 0 0 3px rgba(138, 90, 68, 0.1);
        outline: none;
    }

    .customer-directory-page .view-toolbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 18px;
        gap: 12px;
        flex-wrap: wrap;
    }

    .customer-directory-page .view-toggle {
        display: flex;
        gap: 10px;
    }

    .customer-directory-page .view-btn {
        width: 40px;
        height: 40px;
        border: 2px solid var(--light-border);
        border-radius: var(--radius-md);
        background: white;
        color: var(--gray-600);
        cursor: pointer;
    }

    .customer-directory-page .view-btn.active {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }

    .customer-directory-page .customer-cards {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(300px, 1fr));
        gap: 20px;
        margin-bottom: 24px;
    }

    .customer-directory-page .customer-card {
        background: white;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-sm);
        border: 2px solid transparent;
        overflow: hidden;
        transition: all var(--transition-normal);
    }

    .customer-directory-page .customer-card:hover {
        transform: translateY(-4px);
        box-shadow: var(--shadow-lg);
        border-color: var(--primary);
    }

    .customer-directory-page .card-header {
        display: flex;
        align-items: center;
        justify-content: flex-start;
        gap: 10px;
        padding: 22px;
        border-bottom: 1px solid var(--light-border);
        background: linear-gradient(135deg, var(--primary-bg), white);
    }

    .customer-directory-page .customer-avatar-large {
        width: 72px;
        height: 72px;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        color: white;
        font-size: var(--text-xl);
        font-weight: var(--font-bold);
    }

    .customer-directory-page .customer-name {
        margin: 0 0 4px;
        text-align: center;
        font-size: var(--text-xl);
    }

    .customer-directory-page .customer-created {
        text-align: center;
        font-size: var(--text-sm);
        color: var(--gray-600);
        margin: 0;
    }

    .relative {
        position: relative;
    }

    .customer-directory-page .customer-type-wrap {
        position: absolute;
        top: 10px;
        right: 10px;
    }

    .customer-directory-page .customer-type-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        border-radius: var(--radius-full);
        padding: 6px 12px;
        font-size: var(--text-xs);
        font-weight: var(--font-semibold);
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    .customer-directory-page .customer-type-retail {
        background: var(--success-bg);
        color: var(--success);
    }

    .customer-directory-page .customer-type-wholesale {
        background: var(--info-bg);
        color: var(--info);
    }

    .customer-directory-page .customer-type-custom {
        background: var(--primary-bg);
        color: var(--primary);
    }

    .customer-directory-page .card-body {
        padding: 22px;
    }

    .customer-directory-page .info-item {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 12px;
        color: var(--gray-700);
        font-size: var(--text-sm);
    }

    .customer-directory-page .info-item i {
        width: 18px;
        color: var(--primary);
    }

    .customer-directory-page .card-actions {
        display: flex;
        align-items: center;
        gap: 8px;
        margin-top: 8px;
    }

    .customer-directory-page .btn-icon {
        width: 38px;
        height: 38px;
        border-radius: var(--radius-md);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        border: 0;
        cursor: pointer;
    }

    .customer-directory-page .btn-edit {
        background: var(--warning-bg);
        color: #856404;
    }

    .customer-directory-page .btn-delete {
        background: var(--danger-bg);
        color: var(--danger);
    }

    .customer-directory-page .table-container {
        background: white;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-sm);
        overflow: hidden;
        margin-bottom: 24px;
    }

    .customer-directory-page .customer-name-cell {
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .customer-directory-page .table-avatar {
        width: 40px;
        height: 40px;
        border-radius: 50%;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        background: var(--primary-bg);
        color: var(--primary);
        font-weight: var(--font-bold);
    }

    .customer-directory-page .empty-state {
        text-align: center;
        padding: 42px 20px;
        background: white;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-sm);
    }

    .customer-directory-page .empty-icon {
        font-size: var(--text-5xl);
        color: var(--gray-400);
        margin-bottom: 12px;
    }

    .customer-directory-page .table-actions {
        display: flex;
        gap: 8px;
        align-items: center;
    }

    .customer-directory-page .inline-form {
        margin: 0;
    }

    @media (max-width: 768px) {
        .customer-directory-page .filter-bar {
            padding: 16px;
        }

        .customer-directory-page .filter-grid {
            grid-template-columns: 1fr;
            padding: 12px;
            gap: 12px;
        }

        .customer-directory-page .customer-cards {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection

@section('content')
<div class="customer-directory-page">
    <div class="page-header-actions">
        <div>
            <h1 class="text-dark">Customer Directory</h1>
            <p>Manage customer profiles and contact details with a unified view.</p>
        </div>
        <div class="action-buttons">
            @canany(['manage-customers', 'create-customers'])
            <a href="{{ route('customer.create') }}" class="btn btn-primary">
                <i class="fas fa-user-plus"></i> Add Customer
            </a>
            @endcanany
        </div>
    </div>

    @if (session('success'))
    <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <div class="customer-stats">
        <div class="stat-card">
            <div class="stat-icon"><i class="fas fa-users"></i></div>
            <div class="stat-value">{{ number_format($stats['total']) }}</div>
            <div class="stat-label">Total Customers</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--success-bg); color: var(--success);"><i class="fas fa-calendar-week"></i></div>
            <div class="stat-value">{{ number_format($stats['added_this_week']) }}</div>
            <div class="stat-label">Added This Week</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--info-bg); color: var(--info);"><i class="fas fa-calendar-alt"></i></div>
            <div class="stat-value">{{ number_format($stats['added_this_month']) }}</div>
            <div class="stat-label">Added This Month</div>
        </div>
        <div class="stat-card">
            <div class="stat-icon" style="background: var(--warning-bg); color: #856404;"><i class="fas fa-clock-rotate-left"></i></div>
            <div class="stat-value">{{ number_format($stats['added_last_30_days']) }}</div>
            <div class="stat-label">Last 30 Days</div>
        </div>
    </div>

    <div class="filter-bar">
        <div class="filter-head">
            <div class="filter-head-meta">
                <h3 class="filter-title">Find Customers Faster</h3>
                <p class="filter-subtitle">Search, sort, and narrow results by time and customer type.</p>
            </div>
            <button id="clearFiltersBtn" type="button" class="btn btn-light btn-sm clear-filters-btn">
                <i class="fas fa-times"></i> Clear Filters
            </button>
        </div>
        <div class="filter-grid">
            <div class="filter-control search-field">
                <label class="filter-label" for="customerSearchInput">Search</label>
                <div class="search-box-large">
                    <i class="fas fa-search"></i>
                    <input id="customerSearchInput" type="text" placeholder="Search by name, email, phone, or address...">
                </div>
            </div>
            <div class="filter-control">
                <label class="filter-label" for="createdFilter">Added</label>
                <div class="filter-select-wrap">
                    <i class="fas fa-calendar-days"></i>
                    <select id="createdFilter" class="form-control">
                        <option value="all">All Time</option>
                        <option value="7">Last 7 Days</option>
                        <option value="30">Last 30 Days</option>
                        <option value="90">Last 90 Days</option>
                    </select>
                </div>
            </div>
            <div class="filter-control">
                <label class="filter-label" for="sortFilter">Sort By</label>
                <div class="filter-select-wrap">
                    <i class="fas fa-arrow-down-wide-short"></i>
                    <select id="sortFilter" class="form-control">
                        <option value="newest">Most Recent</option>
                        <option value="oldest">Oldest</option>
                        <option value="name_asc">Name (A-Z)</option>
                        <option value="name_desc">Name (Z-A)</option>
                    </select>
                </div>
            </div>
            <div class="filter-control">
                <label class="filter-label" for="typeFilter">Customer Type</label>
                <div class="filter-select-wrap">
                    <i class="fas fa-users-viewfinder"></i>
                    <select id="typeFilter" class="form-control">
                        <option value="all">All Types</option>
                        <option value="retail">Retail</option>
                        <option value="wholesale">Wholesale</option>
                        <option value="custom">Custom / VIP</option>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="view-toolbar">
        <div class="view-toggle">
            <button type="button" class="view-btn active" data-view-btn="cards" title="Cards View">
                <i class="fas fa-th-large"></i>
            </button>
            <button type="button" class="view-btn" data-view-btn="table" title="Table View">
                <i class="fas fa-list"></i>
            </button>
        </div>
        <div class="text-muted">
            Showing <strong id="visibleCustomerCount">{{ $customers->count() }}</strong> of <strong>{{ $customers->count() }}</strong> customers on this page
        </div>
    </div>

    <div id="customerCardsView">
        <div class="customer-cards" id="customerCards">
            @foreach ($customers as $customer)
            @php
            $customerType = $customer->customer_type ?: 'retail';
            $customerTypeLabel = match ($customerType) {
            'wholesale' => 'Wholesale',
            'custom' => 'Custom / VIP',
            default => 'Retail',
            };
            $initials = collect(explode(' ', $customer->name))
            ->filter()
            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
            ->take(2)
            ->implode('');
            @endphp
            <article
                class="customer-card"
                data-customer-item
                data-name="{{ strtolower($customer->name) }}"
                data-created="{{ $customer->created_at->timestamp }}"
                data-type="{{ $customerType }}"
                data-search="{{ strtolower($customer->name . ' ' . $customer->email . ' ' . $customer->phone . ' ' . $customer->address) }}">
                <div class="card-header relative">
                    <div class="customer-avatar-large">{{ $initials }}</div>
                    <h3 class="customer-name">{{ $customer->name }}</h3>
                    <div class="customer-type-wrap">
                        <span class="customer-type-badge customer-type-{{ $customerType }}">
                            {{ $customerTypeLabel }}
                        </span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="info-item"><i class="fas fa-envelope"></i> {{ $customer->email }}</div>
                    <div class="info-item"><i class="fas fa-phone"></i> {{ $customer->phone }}</div>
                    <div class="info-item"><i class="fas fa-location-dot"></i> {{ $customer->address }}</div>
                    <div class="card-actions">
                        @canany(['manage-customers', 'view-customers'])
                        <a href="{{ route('customer.show', $customer) }}" class="btn btn-sm btn-info" title="View">
                            <i class="fas fa-eye"></i>
                        </a>
                        @endcanany
                        @canany(['manage-customers', 'edit-customers'])
                        <a href="{{ route('customer.edit', $customer) }}" class="btn-icon btn-edit" title="Edit">
                            <i class="fas fa-pen"></i>
                        </a>
                        @endcanany
                        @canany(['manage-customers', 'delete-customers'])
                        <form
                            action="{{ route('customer.destroy', $customer) }}"
                            method="POST"
                            class="inline-form"
                            onsubmit="return confirm('Delete this customer?');">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="btn-icon btn-delete" title="Delete">
                                <i class="fas fa-trash"></i>
                            </button>
                        </form>
                        @endcanany
                    </div>
                </div>
            </article>
            @endforeach
        </div>
    </div>

    <div id="customerTableView" style="display: none;">
        <div class="table-container">
            <table class="table">
                <thead>
                    <tr>
                        <th>Customer</th>
                        <th>Type</th>
                        <th>Email</th>
                        <th>Phone</th>
                        <th>Address</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="customerTableBody">
                    @forelse ($customers as $customer)
                    @php
                    $customerType = $customer->customer_type ?: 'retail';
                    $customerTypeLabel = match ($customerType) {
                    'wholesale' => 'Wholesale',
                    'custom' => 'Custom / VIP',
                    default => 'Retail',
                    };
                    $initials = collect(explode(' ', $customer->name))
                    ->filter()
                    ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                    ->take(2)
                    ->implode('');
                    @endphp
                    <tr
                        data-customer-item
                        data-name="{{ strtolower($customer->name) }}"
                        data-created="{{ $customer->created_at->timestamp }}"
                        data-type="{{ $customerType }}"
                        data-search="{{ strtolower($customer->name . ' ' . $customer->email . ' ' . $customer->phone . ' ' . $customer->address) }}">
                        <td>
                            <div class="customer-name-cell">
                                <span class="table-avatar">{{ $initials }}</span>
                                <strong>{{ $customer->name }}</strong>
                            </div>
                        </td>
                        <td>
                            <span class="customer-type-badge customer-type-{{ $customerType }}">
                                {{ $customerTypeLabel }}
                            </span>
                        </td>
                        <td>{{ $customer->email }}</td>
                        <td>{{ $customer->phone }}</td>
                        <td>{{ $customer->address }}</td>
                        <td>{{ $customer->created_at->format('M d, Y') }}</td>
                        <td>
                            <div class="table-actions">
                                @canany(['manage-customers', 'view-customers'])
                                <a href="{{ route('customer.show', $customer) }}" class="btn btn-sm btn-info">View</a>
                                @endcanany
                                @canany(['manage-customers', 'edit-customers'])
                                <a href="{{ route('customer.edit', $customer) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany
                                @canany(['manage-customers', 'delete-customers'])
                                <form
                                    action="{{ route('customer.destroy', $customer) }}"
                                    method="POST"
                                    class="inline-form"
                                    onsubmit="return confirm('Delete this customer?');">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                </form>
                                @endcanany
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="empty">No customers found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <div id="customerEmptyState" class="empty-state" style="display: none;">
        <div class="empty-icon"><i class="fas fa-users-slash"></i></div>
        <h3>No Customers Found</h3>
        <p>Try another search or filter combination.</p>
    </div>

    @if ($customers->hasPages())
    <div class="pagination">
        {{ $customers->links() }}
    </div>
    @endif
</div>
@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', () => {
        const searchInput = document.getElementById('customerSearchInput');
        const createdFilter = document.getElementById('createdFilter');
        const sortFilter = document.getElementById('sortFilter');
        const typeFilter = document.getElementById('typeFilter');
        const clearFiltersBtn = document.getElementById('clearFiltersBtn');
        const cardsView = document.getElementById('customerCardsView');
        const tableView = document.getElementById('customerTableView');
        const emptyState = document.getElementById('customerEmptyState');
        const countEl = document.getElementById('visibleCustomerCount');
        const cards = Array.from(document.querySelectorAll('#customerCards [data-customer-item]'));
        const rows = Array.from(document.querySelectorAll('#customerTableBody [data-customer-item]'));
        const viewButtons = Array.from(document.querySelectorAll('[data-view-btn]'));
        const tableBody = document.getElementById('customerTableBody');
        const cardsContainer = document.getElementById('customerCards');
        const now = Math.floor(Date.now() / 1000);

        let currentView = 'cards';

        const showView = (view) => {
            currentView = view;
            cardsView.style.display = view === 'cards' ? '' : 'none';
            tableView.style.display = view === 'table' ? '' : 'none';

            viewButtons.forEach((button) => {
                button.classList.toggle('active', button.dataset.viewBtn === view);
            });
        };

        const sortItems = () => {
            const mode = sortFilter.value;
            const byNameAsc = (a, b) => a.dataset.name.localeCompare(b.dataset.name);
            const byDateDesc = (a, b) => Number(b.dataset.created) - Number(a.dataset.created);
            const byDateAsc = (a, b) => Number(a.dataset.created) - Number(b.dataset.created);
            let sorter = byDateDesc;

            if (mode === 'name_asc') sorter = byNameAsc;
            if (mode === 'name_desc') sorter = (a, b) => byNameAsc(b, a);
            if (mode === 'oldest') sorter = byDateAsc;

            cards.sort(sorter).forEach((card) => cardsContainer.appendChild(card));
            rows.sort(sorter).forEach((row) => tableBody.appendChild(row));
        };

        const passesDateFilter = (timestamp, days) => {
            if (days === 'all') return true;
            return now - Number(timestamp) <= Number(days) * 86400;
        };

        const applyFilters = () => {
            const query = searchInput.value.trim().toLowerCase();
            const days = createdFilter.value;
            const type = typeFilter.value;
            let visibleCount = 0;

            cards.forEach((card) => {
                const matchesSearch = card.dataset.search.includes(query);
                const matchesDate = passesDateFilter(card.dataset.created, days);
                const matchesType = type === 'all' || card.dataset.type === type;
                const show = matchesSearch && matchesDate && matchesType;

                card.style.display = show ? '' : 'none';
                if (show) visibleCount++;
            });

            rows.forEach((row) => {
                const matchesSearch = row.dataset.search.includes(query);
                const matchesDate = passesDateFilter(row.dataset.created, days);
                const matchesType = type === 'all' || row.dataset.type === type;
                row.style.display = matchesSearch && matchesDate && matchesType ? '' : 'none';
            });

            countEl.textContent = String(visibleCount);

            const hasResults = visibleCount > 0;
            emptyState.style.display = hasResults ? 'none' : '';

            if (!hasResults) {
                cardsView.style.display = 'none';
                tableView.style.display = 'none';
                return;
            }

            showView(currentView);
        };

        searchInput.addEventListener('input', applyFilters);
        createdFilter.addEventListener('change', applyFilters);
        typeFilter.addEventListener('change', applyFilters);
        sortFilter.addEventListener('change', () => {
            sortItems();
            applyFilters();
        });

        clearFiltersBtn.addEventListener('click', () => {
            searchInput.value = '';
            createdFilter.value = 'all';
            typeFilter.value = 'all';
            sortFilter.value = 'newest';
            sortItems();
            applyFilters();
        });

        viewButtons.forEach((button) => {
            button.addEventListener('click', () => showView(button.dataset.viewBtn));
        });

        sortItems();
        applyFilters();
    });
</script>
@endsection