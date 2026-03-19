@extends('layouts.app')

@section('title', 'View Customer')

@section('page-specific-style')
<style>
    /* Breadcrumb Navigation */
    .breadcrumb {
        display: flex;
        align-items: center;
        gap: 10px;
        margin-bottom: 30px;
        color: var(--gray-600);
        font-size: var(--text-sm);
    }

    .breadcrumb a {
        color: var(--primary);
        text-decoration: none;
    }

    .breadcrumb a:hover {
        text-decoration: underline;
    }

    .breadcrumb i {
        font-size: var(--text-xs);
    }

    /* Profile Header */
    .profile-header {
        background: white;
        border-radius: var(--radius-card);
        padding: 40px;
        box-shadow: var(--shadow-sm);
        margin-bottom: 30px;
        position: relative;
        overflow: hidden;
    }

    .profile-header::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        right: 0;
        height: 4px;
        background: linear-gradient(90deg, var(--primary), var(--secondary));
    }

    .profile-content {
        display: flex;
        align-items: center;
        gap: 40px;
        flex-wrap: wrap;
    }

    .profile-avatar {
        position: relative;
    }

    .avatar-large {
        width: 150px;
        height: 150px;
        border-radius: 50%;
        background: linear-gradient(135deg, var(--primary), var(--secondary));
        display: flex;
        align-items: center;
        justify-content: center;
        color: white;
        font-size: var(--text-4xl);
        font-weight: var(--font-bold);
        border: 5px solid white;
        box-shadow: var(--shadow-md);
    }

    .avatar-status {
        position: absolute;
        bottom: 10px;
        right: 10px;
        width: 20px;
        height: 20px;
        border-radius: 50%;
        border: 3px solid white;
    }

    .status-vip {
        background: var(--purple);
    }

    .profile-info {
        flex: 1;
        min-width: 300px;
    }

    .profile-name {
        font-size: var(--text-3xl);
        font-weight: var(--font-bold);
        color: var(--dark);
        margin-bottom: 10px;
    }

    .profile-category {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 8px 20px;
        background: var(--primary-bg);
        color: var(--primary);
        border-radius: var(--radius-full);
        font-size: var(--text-sm);
        font-weight: var(--font-semibold);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 20px;
    }

    .profile-contact {
        display: flex;
        flex-wrap: wrap;
        gap: 30px;
        margin-bottom: 25px;
    }

    .contact-item {
        display: flex;
        align-items: center;
        gap: 10px;
        color: var(--gray-700);
    }

    .contact-item i {
        width: 20px;
        color: var(--primary);
    }

    .profile-meta {
        display: flex;
        gap: 30px;
        flex-wrap: wrap;
    }

    .meta-item {
        display: flex;
        flex-direction: column;
    }

    .meta-label {
        font-size: var(--text-xs);
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 5px;
    }

    .meta-value {
        font-size: var(--text-lg);
        font-weight: var(--font-semibold);
        color: var(--dark);
    }

    .profile-actions {
        margin-top: 10px;
        display: flex;
        gap: 15px;
        flex-wrap: wrap;
    }

    .action-btn {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 12px 25px;
        border-radius: var(--radius-md);
        font-weight: var(--font-medium);
        cursor: pointer;
        transition: all var(--transition-normal);
        text-decoration: none;
        border: none;
    }

    .btn-edit {
        background: var(--warning);
        color: var(--dark);
    }

    .btn-edit:hover {
        background: #e0a800;
        transform: translateY(-2px);
    }

    .btn-order {
        background: var(--primary);
        color: white;
    }

    .btn-order:hover {
        background: var(--primary-dark);
        transform: translateY(-2px);
    }

    /* Tab Navigation */
    .tab-navigation {
        background: white;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-sm);
        margin-bottom: 30px;
        overflow: hidden;
    }

    .tab-header {
        display: flex;
        border-bottom: 2px solid var(--light-border);
        overflow: hidden;
    }

    .tab-btn {
        padding: 20px 30px;
        background: none;
        border: none;
        font-size: var(--text-base);
        font-weight: var(--font-medium);
        color: var(--gray-600);
        cursor: pointer;
        transition: all var(--transition-normal);
        white-space: nowrap;
        position: relative;
    }

    .tab-btn.active {
        font-weight: var(--font-semibold);
    }

    .tab-badge {
        background: var(--primary);
        color: white;
        font-size: var(--text-xs);
        padding: 2px 6px;
        border-radius: var(--radius-full);
        margin-left: 8px;
    }

    .tab-content {
        display: none;
        padding: 30px;
    }

    .tab-content.active {
        display: block;
        animation: fadeIn 0.3s ease;
    }

    /* Tab 1: Complete Profile */
    .profile-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
        gap: 30px;
    }

    .info-section {
        background: white;
        border-radius: var(--radius-card);
        padding: 25px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--light-border);
    }

    .section-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 25px;
        padding-bottom: 15px;
        border-bottom: 2px solid var(--light-border);
    }

    .section-title {
        font-size: var(--text-lg);
        font-weight: var(--font-semibold);
        color: var(--dark);
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .section-title i {
        color: var(--primary);
    }

    .info-grid {
        display: grid;
        gap: 15px;
    }

    .info-row {
        display: grid;
        grid-template-columns: 150px 1fr;
        align-items: center;
        gap: 15px;
        padding: 10px 0;
        border-bottom: 1px solid var(--light-border);
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-label {
        font-weight: var(--font-medium);
        color: var(--gray-700);
    }

    .info-value {
        color: var(--dark);
    }

    .info-value.empty {
        color: var(--gray-500);
        font-style: italic;
    }

    /* Tab 2: Measurement History */
    .measurement-timeline {
        position: relative;
        padding-left: 30px;
    }

    .measurement-timeline::before {
        content: '';
        position: absolute;
        left: 0;
        top: 0;
        bottom: 0;
        width: 2px;
        background: var(--light-border);
    }

    .measurement-entry {
        position: relative;
        margin-bottom: 30px;
        padding: 20px;
        background: white;
        border-radius: var(--radius-card);
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--light-border);
    }

    .measurement-entry::before {
        content: '';
        position: absolute;
        left: -36px;
        top: 25px;
        width: 12px;
        height: 12px;
        border-radius: 50%;
        background: var(--primary);
        border: 3px solid white;
        box-shadow: 0 0 0 3px var(--primary);
    }

    .measurement-date {
        font-size: var(--text-sm);
        color: var(--gray-600);
        margin-bottom: 10px;
    }

    .measurement-purpose {
        display: inline-block;
        padding: 5px 12px;
        background: var(--primary-bg);
        color: var(--primary);
        border-radius: var(--radius-full);
        font-size: var(--text-xs);
        font-weight: var(--font-semibold);
        margin-bottom: 15px;
    }

    .measurement-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
        margin-bottom: 15px;
    }

    .measurement-item {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 10px;
        background: var(--gray-50);
        border-radius: var(--radius-md);
    }

    .measurement-value {
        font-size: var(--text-lg);
        font-weight: var(--font-bold);
        color: var(--dark);
        margin-bottom: 5px;
    }

    .measurement-label {
        font-size: var(--text-xs);
        color: var(--gray-600);
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Quick Actions Sidebar */
    .quick-actions-sidebar {
        position: sticky;
        top: 100px;
        background: white;
        border-radius: var(--radius-card);
        padding: 25px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--light-border);
    }

    .action-list {
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .action-item {
        display: flex;
        align-items: center;
        gap: 15px;
        padding: 15px;
        border-radius: var(--radius-md);
        text-decoration: none;
        color: var(--gray-700);
        transition: all var(--transition-normal);
    }

    .action-item:hover {
        background: var(--primary-bg);
        color: var(--primary);
        transform: translateX(5px);
    }

    .action-icon {
        width: 40px;
        height: 40px;
        border-radius: var(--radius-md);
        background: var(--gray-100);
        display: flex;
        align-items: center;
        justify-content: center;
        color: var(--primary);
        font-size: var(--text-lg);
    }

    /* Responsive */
    @media (max-width: 1200px) {
        .profile-content {
            gap: 30px;
        }

        .profile-grid {
            grid-template-columns: 1fr;
        }
    }

    @media (max-width: 768px) {
        .customer-detail-container {
            padding: 10px;
        }

        .profile-header {
            padding: 30px 20px;
        }

        .avatar-large {
            width: 120px;
            height: 120px;
            font-size: var(--text-3xl);
        }

        .profile-name {
            font-size: var(--text-2xl);
        }

        .tab-btn {
            padding: 15px 20px;
            font-size: var(--text-sm);
        }

        .tab-content {
            padding: 20px;
        }

        .info-row {
            grid-template-columns: 1fr;
            gap: 5px;
        }
    }

    @media (max-width: 576px) {
        .profile-contact {
            flex-direction: column;
            gap: 15px;
        }

        .profile-meta {
            gap: 20px;
        }

        .profile-actions {
            flex-direction: column;
            width: 100%;
        }

        .action-btn {
            width: 100%;
            justify-content: center;
        }

        .tab-header {
            flex-direction: column;
        }

        .tab-btn {
            width: 100%;
            text-align: left;
            border-bottom: 1px solid var(--light-border);
        }

        .tab-btn.active::after {
            bottom: 0;
            height: 100%;
            width: 4px;
            right: auto;
        }
    }

    /* Animations */
    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }
</style>
@endsection


@section('content')
@php
$garmentMeasurementSets = $customer->customerGarmentTypes
->sortByDesc('updated_at')
->values();
$measurementSetCount = $garmentMeasurementSets->count();
@endphp

<div class="breadcrumb">
    <a href="{{ route('dashboard') }}"><i class="fas fa-home"></i> Dashboard</a>
    <i class="fas fa-chevron-right"></i>
    <a href="{{ route('customer.index') }}">Customer Directory</a>
    <i class="fas fa-chevron-right"></i>
    <span>{{ $customer->name }}</span>
</div>

<div style="display: grid; grid-template-columns: 1fr 350px; gap: 30px;">
    <div>
        <div class="profile-header">
            <div class="profile-content">
                <div class="profile-avatar">
                    <div class="avatar-large">
                        {{ collect(explode(' ', $customer->name))
                            ->filter()
                            ->map(fn ($part) => strtoupper(substr($part, 0, 1)))
                            ->take(2)
                            ->implode('') }}
                    </div>
                    <div class="avatar-status status-vip" title="VIP Customer"></div>
                </div>

                <div class="profile-info">
                    <div class="profile-name">{{ $customer->name }}</div>
                    <div class="profile-category">
                        <i class="fas fa-star"></i>
                        {{ ucfirst($customer->customer_type ?? 'Retail') }}
                    </div>

                    <div class="profile-contact">
                        <div class="contact-item">
                            <i class="fas fa-phone"></i>
                            <span>{{ $customer->phone }}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-envelope"></i>
                            <span>{{ $customer->email }}</span>
                        </div>
                        <div class="contact-item">
                            <i class="fas fa-map-marker-alt"></i>
                            <span>{{ $customer->address }}</span>
                        </div>
                    </div>

                    <div class="profile-meta">
                        <div class="meta-item">
                            <div class="meta-label">Total Orders</div>
                            <div class="meta-value">{{ number_format($orderCount) }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Total Spent</div>
                            <div class="meta-value">₹{{ number_format($totalSpent, 2) }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Member Since</div>
                            <div class="meta-value">{{ $customer->created_at->format('M d, Y') }}</div>
                        </div>
                        <div class="meta-item">
                            <div class="meta-label">Last Order</div>
                            <div class="meta-value">{{ $lastOrderDate ? \Illuminate\Support\Carbon::parse($lastOrderDate)->format('M d, Y') : 'No orders yet' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="profile-actions">
                <a href="{{ route('customer.edit', ['customer' => $customer]) }}" class="action-btn btn-edit">
                    <i class="fas fa-edit"></i> Edit Profile
                </a>
                @canany(['create-orders', 'manage-orders'])
                    <a href="{{ route('order.create', ['customer_id' => $customer->id]) }}" class="action-btn btn-order">
                        <i class="fas fa-plus"></i> Create Order
                    </a>
                @endcanany
            </div>
        </div>
        <div class="tab-navigation">
            <div class="tab-header">
                <button class="tab-btn active" data-tab="profile">
                    <i class="fas fa-user"></i> Complete Profile
                </button>
                <button class="tab-btn" data-tab="measurements">
                    <i class="fas fa-ruler"></i> Measurement History
                    <span class="tab-badge">{{ $measurementSetCount }}</span>
                </button>
                <button class="tab-btn" data-tab="orders">
                    <i class="fas fa-file-invoice"></i> Orders
                    <span class="tab-badge">{{ $orderCount }}</span>
                </button>
            </div>

            <!-- Tab 1: Complete Profile -->
            <div class="tab-content active" id="tab-profile">
                <div class="profile-grid">
                    <!-- Personal Information -->
                    <div class="info-section">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-id-card"></i>
                                Personal Information
                            </div>
                            <a href="{{ route('customer.edit', ['customer' => $customer]) }}" class="btn btn-sm btn-outline-primary" onclick="editPersonalInfo()">
                                <i class="fas fa-edit"></i> Edit
                            </a>
                        </div>

                        <div class="info-grid">
                            <div class="info-row">
                                <div class="info-label">Name:</div>
                                <div class="info-value">{{ $customer->name }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Email:</div>
                                <div class="info-value">{{ $customer->email }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Phone Number:</div>
                                <div class="info-value">{{ $customer->phone }}</div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Category:</div>
                                <div class="info-value">
                                    <span class="category-badge category-custom">{{ ucfirst($customer->customer_type) }}</span>
                                </div>
                            </div>
                            <div class="info-row">
                                <div class="info-label">Address:</div>
                                <div class="info-value">
                                    {{ $customer->address }}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tab 2: Measurement History -->
            <div class="tab-content" id="tab-measurements">
                <div style="margin-bottom: 30px;">
                    <a class="btn btn-primary" href="{{ route('customer.edit', ['customer' => $customer, 'tab' => 'measurements']) }}">
                        <i class="fas fa-ruler"></i> Manage Measurements
                    </a>
                </div>

                @if ($measurementSetCount === 0)
                <div class="info-section">
                    <div class="info-value empty">No measurement records available. Add measurements from the customer edit page.</div>
                </div>
                @else
                <div class="measurement-timeline">
                    @foreach ($garmentMeasurementSets as $customerGarmentType)
                    <div class="measurement-entry">
                        <div class="measurement-date">
                            <i class="fas fa-calendar"></i>
                            Updated {{ $customerGarmentType->updated_at?->format('F d, Y') ?? 'N/A' }}
                        </div>
                        <div class="measurement-purpose">
                            <i class="fas fa-tshirt"></i>
                            {{ $customerGarmentType->garmentType?->title ?? 'Unknown Garment Type' }}
                        </div>

                        @if ($customerGarmentType->measurements->isEmpty())
                        <div class="info-value empty">No measurement rows for this garment type.</div>
                        @else
                        <div class="measurement-grid">
                            @foreach ($customerGarmentType->measurements as $measurement)
                            <div class="measurement-item">
                                <div class="measurement-value">{{ $measurement->measurement }}{{ $measurement->unit ? ' ' . $measurement->unit : '' }}</div>
                                <div class="measurement-label">{{ $measurement->type }}</div>
                            </div>
                            @endforeach
                        </div>
                        @endif
                    </div>
                    @endforeach
                </div>
                @endif
            </div>

            <div class="tab-content" id="tab-orders">
                @if ($recentOrders->isEmpty())
                    <div class="info-section">
                        <div class="info-value empty">No orders found for this customer in the current outlet.</div>
                    </div>
                @else
                    <div class="info-section">
                        <div class="section-header">
                            <div class="section-title">
                                <i class="fas fa-file-invoice"></i>
                                Order History
                            </div>
                        </div>

                        <div class="table-container">
                            <table class="table">
                                <thead>
                                    <tr>
                                        <th>Order No</th>
                                        <th>Ordered At</th>
                                        <th>Delivery Due</th>
                                        <th>Status</th>
                                        <th>Payment</th>
                                        <th>Amount</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($recentOrders as $order)
                                        <tr>
                                            <td>{{ $order->order_number }}</td>
                                            <td>{{ $order->ordered_at?->format('M d, Y') ?? '-' }}</td>
                                            <td>{{ $order->delivery_due_at?->format('M d, Y') ?? '-' }}</td>
                                            <td>{{ \App\Models\Order::statusLabel((string) $order->status) }}</td>
                                            <td>{{ ucfirst((string) $order->payment_status) }}</td>
                                            <td>₹{{ number_format($order->payableAmount(), 2) }}</td>
                                            <td>
                                                @canany(['view-orders', 'manage-orders'])
                                                    <a href="{{ route('order.show', $order) }}" class="btn btn-sm btn-info">View Order</a>
                                                @endcanany
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
    <div>
        <!-- Quick Actions Sidebar -->
        <div class="quick-actions-sidebar">
            <h3 style="margin-bottom: 20px; color: var(--dark);">Quick Actions</h3>

            <div class="action-list">
                @canany(['create-orders', 'manage-orders'])
                    <a href="{{ route('order.create', ['customer_id' => $customer->id]) }}" class="action-item">
                        <div class="action-icon">
                            <i class="fas fa-plus"></i>
                        </div>
                        <div>
                            <div style="font-weight: 600;">Create New Order</div>
                            <div style="font-size: var(--text-sm); color: var(--gray-600);">Start new order for this customer</div>
                        </div>
                    </a>
                @endcanany

                <a href="{{ route('customer.edit', ['customer' => $customer, 'tab' => 'measurements']) }}" class="action-item">
                    <div class="action-icon">
                        <i class="fas fa-ruler"></i>
                    </div>
                    <div>
                        <div style="font-weight: 600;">Add Measurements</div>
                        <div style="font-size: var(--text-sm); color: var(--gray-600);">Update body measurements</div>
                    </div>
                </a>
            </div>
        </div>
    </div>
</div>

@endsection

@section('page-specific-script')
<script>
    document.addEventListener('DOMContentLoaded', function() {
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const tabId = this.dataset.tab;
                switchTab(tabId);
            });
        });

        // Set initial tab
        switchTab('profile');
    });

    const CustomerDetail = {
        currentTab: 'profile',
    };

    function switchTab(tabId) {
        CustomerDetail.currentTab = tabId;

        // Update tab buttons
        document.querySelectorAll('.tab-btn').forEach(btn => {
            btn.classList.remove('active');
            if (btn.dataset.tab === tabId) {
                btn.classList.add('active');
            }
        });

        // Update tab content
        document.querySelectorAll('.tab-content').forEach(content => {
            content.classList.remove('active');
            if (content.id === `tab-${tabId}`) {
                content.classList.add('active');
            }
        });

        // Update URL hash
        window.location.hash = tabId;
    }
</script>
@endsection
