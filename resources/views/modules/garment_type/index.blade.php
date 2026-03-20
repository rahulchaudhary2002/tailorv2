@extends('layouts.app')

@section('title', 'Garment Type Management')


@section('content')
<div class="page-header">
    <div class="page-title">
        <h1 class="text-dark">Garment Type Management</h1>
        <p>Manage garment types with full measurement format and multiple rates.</p>
    </div>
    @canany(['manage-garment-types', 'create-garment-types'])
        <div class="page-actions">
            <a href="{{ route('garmentType.create') }}" class="btn btn-primary">
                <i class="fas fa-plus"></i> Add Garment Type
            </a>
        </div>
    @endcanany
</div>

@include('includes.reporting-filter', ['paginator' => $garmentTypes, 'placeholder' => 'Search by garment title...', 'reporting' => $reporting])

<div class="table-card">
    @if (session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    @if (session('error'))
        <div class="alert alert-danger">{{ session('error') }}</div>
    @endif

    <div class="table-header">
        <div class="table-title">Garment Types</div>
    </div>

    <style>
        .table th,
        .table td {
            text-align: center;
            vertical-align: middle;
        }

        .garment-detail-stack {
            display: flex;
            flex-direction: column;
            justify-content: center;
            gap: 8px;
            min-width: 220px;
            height: 100%;
        }

        .garment-detail-stack--compact {
            min-width: 200px;
        }

        .garment-detail-stack--two-column {
            display: grid;
            grid-template-columns: repeat(2, minmax(0, 1fr));
            gap: 8px;
            align-items: start;
        }

        @media (max-width: 768px) {
            .garment-detail-stack--two-column {
                grid-template-columns: 1fr;
            }
        }

        .garment-detail-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 12px;
            padding: 8px 10px;
            border: 1px solid #e1eaf4;
            border-radius: 10px;
            background: #f8fbff;
        }

        .garment-detail-row span:first-child {
            color: #516274;
        }

        .garment-detail-row strong {
            color: #1f2d3d;
            text-align: center;
        }
    </style>

    <div class="table-container">
        <table class="table">
            <thead>
                <tr>
                    <th>SN</th>
                    <th>Garment Type</th>
                    <th>Measurement Format</th>
                    <th>Rates</th>
                    <th>Created</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                @forelse ($garmentTypes as $garmentType)
                    <tr>
                        <td>{{ ($garmentTypes->firstItem() ?? 1) + $loop->index }}</td>
                        <td style="font-weight: 600;">
                            {{ $garmentType->title }}
                        </td>
                        <td>
                            @if ($garmentType->measurements->isNotEmpty())
                                <div class="garment-detail-stack garment-detail-stack--two-column">
                                    @foreach ($garmentType->measurements as $measurement)
                                        <div class="garment-detail-row">
                                            <span>{{ $measurement->order }}.</span>
                                            <strong>
                                                {{ $measurement->title }}
                                                @if ($measurement->unit?->symbol)
                                                    ({{ $measurement->unit->symbol }})
                                                @endif
                                            </strong>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td style="width: 250px;">
                            @if ($garmentType->tailoringPackages->isNotEmpty())
                                <div class="garment-detail-stack garment-detail-stack--compact">
                                    @foreach ($garmentType->tailoringPackages as $package)
                                        <div class="garment-detail-row">
                                            <span>{{ $package->name }}</span>
                                            <strong>{{ number_format((float) $package->amount, 2) }}</strong>
                                        </div>
                                    @endforeach
                                </div>
                            @else
                                -
                            @endif
                        </td>
                        <td>
                            {{ $garmentType->created_at->format('M d, Y') }}
                        </td>
                        <td>
                            <div class="actions">
                                @canany(['manage-garment-types', 'edit-garment-types'])
                                    <a href="{{ route('garmentType.edit', $garmentType) }}" class="btn btn-sm btn-secondary">Edit</a>
                                @endcanany

                                @canany(['manage-garment-types', 'delete-garment-types'])
                                    <form
                                        action="{{ route('garmentType.destroy', $garmentType) }}"
                                        method="POST"
                                        class="inline-form"
                                        onsubmit="return confirm('Delete this garment type and all measurements?');"
                                    >
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
                        <td colspan="6" class="empty">No garment types found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if ($garmentTypes->hasPages())
        <div class="pagination">
            {{ $garmentTypes->links() }}
        </div>
    @endif
</div>
@endsection
