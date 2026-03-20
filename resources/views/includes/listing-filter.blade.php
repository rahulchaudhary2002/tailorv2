@php
    $query = trim((string) request('q', ''));
@endphp

<div class="directory-reporting">
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
