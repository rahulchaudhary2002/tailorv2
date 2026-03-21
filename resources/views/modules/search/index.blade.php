@extends('layouts.app')

@section('title', 'Global Search')

@section('content')
@php
    $totalResults = collect($results)->sum(fn ($group) => $group->count());
@endphp

<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Global Search</h1>
        <p>
            @if ($query !== '')
                {{ number_format($totalResults) }} result{{ $totalResults === 1 ? '' : 's' }} for "{{ $query }}".
            @else
                Search orders, customers, products, workers, and tasks from the header.
            @endif
        </p>
    </div>
</div>

<div class="directory-reporting" style="margin-bottom: 16px;">
    <div class="directory-reporting__filter-bar">
        <form method="GET" class="listing-filter-form">
            <div class="listing-filter-form__fields listing-filter-form__fields--search-page">
                <div class="outlet-form-group listing-filter-form__field listing-filter-form__field--search">
                    <label for="global_search_q">Search</label>
                    <input
                        id="global_search_q"
                        type="text"
                        name="q"
                        class="outlet-input"
                        value="{{ $query }}"
                        placeholder="Search by order no, customer, product, worker, task..."
                    >
                </div>
            </div>

            <div class="listing-filter-form__actions">
                <button type="submit" class="btn btn-primary">Search</button>
                <a href="{{ route('search.index') }}" class="btn btn-secondary">Reset</a>
            </div>
        </form>
    </div>
</div>

@if ($query === '')
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">Start Searching</div>
        </div>
        <div style="padding: 18px 20px; color: #6b7280;">
            Use the search box above or the header search to find records across the app.
        </div>
    </div>
@elseif ($totalResults === 0)
    <div class="table-card">
        <div class="table-header">
            <div class="table-title">No Results</div>
        </div>
        <div style="padding: 18px 20px; color: #6b7280;">
            No matching records were found for "{{ $query }}".
        </div>
    </div>
@else
    <div class="search-results-grid">
        @if ($results['orders']->isNotEmpty())
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Orders</div>
                </div>
                <div class="search-result-list">
                    @foreach ($results['orders'] as $order)
                        <a href="{{ route('order.show', $order) }}" class="search-result-item">
                            <div class="search-result-title">{{ $order->order_number }}</div>
                            <div class="search-result-meta">
                                {{ $order->customer?->name ?: 'Walk-in' }}
                                @if ($order->customer?->phone)
                                    | {{ $order->customer->phone }}
                                @endif
                                @if ($order->outlet?->name)
                                    | {{ $order->outlet->name }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($results['customers']->isNotEmpty())
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Customers</div>
                </div>
                <div class="search-result-list">
                    @foreach ($results['customers'] as $customer)
                        <a href="{{ route('customer.show', $customer) }}" class="search-result-item">
                            <div class="search-result-title">{{ $customer->name }}</div>
                            <div class="search-result-meta">
                                {{ $customer->phone ?: '-' }}
                                @if ($customer->email)
                                    | {{ $customer->email }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($results['products']->isNotEmpty())
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Products</div>
                </div>
                <div class="search-result-list">
                    @foreach ($results['products'] as $product)
                        <a href="{{ route('product.index', ['q' => $product->code ?: $product->name]) }}" class="search-result-item">
                            <div class="search-result-title">{{ $product->name }}</div>
                            <div class="search-result-meta">
                                {{ $product->code ?: '-' }}
                                @if ($product->category?->name)
                                    | {{ $product->category->name }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($results['workers']->isNotEmpty())
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Workers</div>
                </div>
                <div class="search-result-list">
                    @foreach ($results['workers'] as $worker)
                        <a href="{{ route('worker.tasks', $worker) }}" class="search-result-item">
                            <div class="search-result-title">{{ $worker->name }}</div>
                            <div class="search-result-meta">{{ $worker->email ?: '-' }}</div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif

        @if ($results['tasks']->isNotEmpty())
            <div class="table-card">
                <div class="table-header">
                    <div class="table-title">Tasks</div>
                </div>
                <div class="search-result-list">
                    @foreach ($results['tasks'] as $task)
                        @php
                            $taskLink = auth()->user()?->hasPermission('view-task-management')
                                || auth()->user()?->hasPermission('manage-task-management')
                                || auth()->user()?->hasPermission('manage-orders')
                                ? route('taskManagement.index', ['q' => $task->task_number ?: $task->task_title])
                                : route('order.assignedJobs', ['q' => $task->task_number ?: $task->task_title]);
                        @endphp
                        <a href="{{ $taskLink }}" class="search-result-item">
                            <div class="search-result-title">{{ $task->task_number ?: $task->task_title }}</div>
                            <div class="search-result-meta">
                                {{ $task->task_title }}
                                @if ($task->order?->order_number)
                                    | Order {{ $task->order->order_number }}
                                @endif
                                @if ($task->order?->customer?->name)
                                    | {{ $task->order->customer->name }}
                                @endif
                            </div>
                        </a>
                    @endforeach
                </div>
            </div>
        @endif
    </div>
@endif
@endsection

@section('page-specific-style')
<style>
    .listing-filter-form {
        display: grid;
        gap: 14px;
    }

    .listing-filter-form__fields--search-page {
        display: grid;
        grid-template-columns: 1fr;
        gap: 12px;
        align-items: end;
    }

    .listing-filter-form__field {
        margin-bottom: 0;
    }

    .listing-filter-form__actions {
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }

    .search-results-grid {
        display: grid;
        grid-template-columns: repeat(2, minmax(0, 1fr));
        gap: 16px;
    }

    .search-result-list {
        display: grid;
    }

    .search-result-item {
        display: block;
        padding: 14px 18px;
        border-top: 1px solid #eef2f7;
        text-decoration: none;
        color: inherit;
    }

    .search-result-item:hover {
        background: #f8fafc;
    }

    .search-result-title {
        font-weight: 600;
        color: #111827;
    }

    .search-result-meta {
        margin-top: 4px;
        font-size: 13px;
        color: #6b7280;
    }

    @media (max-width: 768px) {
        .listing-filter-form__actions {
            flex-direction: column;
        }

        .listing-filter-form__actions .btn {
            width: 100%;
        }

        .search-results-grid {
            grid-template-columns: 1fr;
        }
    }
</style>
@endsection
