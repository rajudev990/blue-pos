@extends('admin.layouts.app')

@section('title','Due List')

@section('content')
<div class="container pt-4">

    {{-- Filter Form --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fa fa-filter"></i> Filter Due List</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.duelist') }}" class="row g-3">

                <div class="col-md-10">
                    <label for="shop_id" class="form-label">Select Shop</label>
                    <select name="shop_id" id="shop_id" class="form-control select2">
                        <option value="">-- All Shops --</option>
                        @foreach ($shops as $shop)
                        <option value="{{ $shop->id }}"
                            {{ ($filterShopId == $shop->id) ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-success w-100">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Due List Table --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa fa-chart-line"></i> Due List</h5>
        </div>
        <div class="card-body table-responsive">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-dark">
                    <tr>
                        <th>Voucher No</th>
                        <th>Customer</th>
                        <th>Shop</th>
                        <th>Sale Date</th>
                        <th>Price({{ adminCurrency() }})</th>
                        <th>Total Paid({{ adminCurrency() }})</th>
                        <th>Due({{ adminCurrency() }})</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($sales as $sale)
                    <tr>
                        <td>
                            <a href="{{ route('admin.payment', $sale->id) }}">
                                {{ $sale->voucher_no }}
                            </a>
                        </td>
                        <td>{{ $sale->customer->name ?? '' }} - ({{ $sale->customer->phone ?? '' }})</td>
                        <td>{{ $sale->shop->name ?? '' }}</td>
                        <td>{{ $sale->sale_date }}</td>
                        <td>{{ adminCurrency() }}{{ number_format($sale->price, 2) }}</td>
                        <td>{{ adminCurrency() }}{{ number_format($sale->total_paid, 2) }}</td>
                        <td><span class="badge bg-danger">{{ adminCurrency() }}{{ number_format($sale->due, 2) }}</span></td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="text-center text-muted">No due records found.</td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

</div>

@endsection