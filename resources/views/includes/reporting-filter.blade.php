@php
    $query = trim((string) request('q', ''));
    $reporting = $reporting ?? [
        'total' => (int) $paginator->total(),
        'added_this_week' => 0,
        'added_this_month' => 0,
        'added_last_30_days' => 0,
    ];
@endphp

<div class="directory-reporting">
    <div class="directory-reporting__stats">
        <div class="directory-reporting__stat-card">
            <div class="directory-reporting__stat-label">Total Records</div>
            <div class="directory-reporting__stat-value">{{ number_format((int) ($reporting['total'] ?? 0)) }}</div>
        </div>
        <div class="directory-reporting__stat-card">
            <div class="directory-reporting__stat-label">This Week</div>
            <div class="directory-reporting__stat-value">{{ number_format((int) ($reporting['added_this_week'] ?? 0)) }}</div>
        </div>
        <div class="directory-reporting__stat-card">
            <div class="directory-reporting__stat-label">This Month</div>
            <div class="directory-reporting__stat-value">{{ number_format((int) ($reporting['added_this_month'] ?? 0)) }}</div>
        </div>
        <div class="directory-reporting__stat-card">
            <div class="directory-reporting__stat-label">Last 30 Days</div>
            <div class="directory-reporting__stat-value">{{ number_format((int) ($reporting['added_last_30_days'] ?? 0)) }}</div>
        </div>
    </div>

    <div class="directory-reporting__filter-bar">
        <div class="directory-reporting__filter-head">
            <h3 class="directory-reporting__filter-title">Filter Records</h3>
            @if ($query !== '')
                <a href="{{ url()->current() }}" class="btn btn-light btn-sm">Clear Filters</a>
            @endif
        </div>

        <form method="GET" class="directory-reporting__filter-form">
            <div class="outlet-form-group">
                <label for="q_filter">Search</label>
                <input id="q_filter" type="text" name="q" class="outlet-input" value="{{ $query }}" placeholder="{{ $placeholder ?? 'Search...' }}">
            </div>
            <button type="submit" class="btn btn-primary">Apply</button>
            <a href="{{ url()->current() }}" class="btn btn-secondary">Reset</a>
        </form>
    </div>
</div>
