@extends('admin.layouts.app')

@section('title', 'Reports')

@section('content')
<div class="container pt-4">

    {{-- Filter Form --}}
    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa fa-chart-line"></i> Reports</h5>
        </div>
        <div class="card-body">
            <form method="GET" action="{{ route('admin.reports.index') }}" class="row g-3">

                <div class="col-md-2">
                    <label for="shop_id" class="form-label">Shop</label>
                    <select name="shop_id" id="shop_id" class="form-control" required>
                        <option value="">Select Shop</option>
                        @foreach($shops as $shop)
                        <option value="{{ $shop->id }}" {{ request('shop_id') == $shop->id ? 'selected' : '' }}>
                            {{ $shop->name }}
                        </option>
                        @endforeach
                    </select>
                </div>

                <div class="col-md-3">
                    <label for="start_date" class="form-label">Start Date</label>
                    <input type="date" name="start_date" id="start_date"
                        value="{{ request('start_date') }}"
                        class="form-control" required>
                </div>

                <div class="col-md-3">
                    <label for="end_date" class="form-label">End Date</label>
                    <input type="date" name="end_date" id="end_date"
                        value="{{ request('end_date') }}"
                        class="form-control" required>
                </div>

                <div class="col-md-2">
                    <label for="type" class="form-label">Report Type</label>
                    <select name="type" id="type" class="form-control" required>
                        <option value="">Select Type</option>
                        <option value="purchase" {{ request('type')=='purchase' ? 'selected' : '' }}>Purchase</option>
                        <option value="sale" {{ request('type')=='sale' ? 'selected' : '' }}>Sales</option>
                        <option value="expense" {{ request('type')=='expense' ? 'selected' : '' }}>Expense</option>
                    </select>
                </div>



                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fa fa-search"></i> Search
                    </button>
                </div>
            </form>
        </div>
    </div>

    {{-- Report Table --}}
    @if(!empty($type) && $data->count())
    <div class="card shadow-sm">
        <div class="card-header">
            <h5 class="mb-0 text-capitalize">
                {{ $type }} Report ({{ $start }} to {{ $end }})
            </h5>
        </div>
        <div class="card-body table-responsive">
            {{-- তোমার টেবিলের পুরো কোড অপরিবর্তিত থাকবে --}}
            <table class="table table-bordered table-striped">
    <thead class="table-dark">
        <tr>
            <th>#</th>
            @if($type == 'purchase')
                <th>Product</th>
                <th>Date</th>
                <th>Price({{ adminCurrency() }})</th>
                <th>Vat({{ adminCurrency() }})</th>
                <th>Qty</th>
                <th>Per Price({{ adminCurrency() }})</th>
                <th>Stock</th>
                <th>Total Sales</th>
            @elseif($type == 'sale')
                <th>Voucher No</th>
                <th>Customer Name</th>
                <th>Date</th>
                <th>Products</th>
                <th>Price({{ adminCurrency() }})</th>
            @elseif($type == 'expense')
                <th>Date</th>
                <th>Name</th>
                <th>Amount({{ adminCurrency() }})</th>
            @endif
        </tr>
    </thead>
    <tbody>
        @php
            $totalPrice = 0;
            $totalVat = 0;
            $totalQty = 0;
            $totalSalePrice = 0;
            $totalPaid = 0;
            $totalDue = 0;
            $totalExpense = 0;
        @endphp

        @foreach($data as $index => $row)
            <tr>
                <td>{{ $index+1 }}</td>

                {{-- Purchase Report --}}
                @if($type == 'purchase')
                    @php
                        $totalPrice += $row->buy_price;
                        $totalVat += $row->vat;
                        $totalQty += $row->qty;
                    @endphp
                    <td>{{ $row->product?->name }} ({{ $row->unit?->name }})</td>
                    <td>{{ $row->purchases_date }}</td>
                    <td>{{ adminCurrency() }}{{ number_format($row->buy_price,2) }}</td>
                    <td>{{ adminCurrency() }}{{ number_format($row->vat,2) }}</td>
                    <td>{{ number_format($row->qty,2) }}</td>
                    <td>{{ adminCurrency() }}{{ number_format(($row->buy_price + $row->vat) / $row->qty,2) }}</td>
                    <td>{{ $row->stock() }} ({{ $row->unit?->name }})</td>
                    <td>{{ $row->saleCount() }}</td>

                {{-- Sale Report --}}
                @elseif($type == 'sale')
                    @php
                        $totalSalePrice += $row->price;
                        $paid = $row->payments->sum('paid');
                        $due = $row->price - $paid;
                        $totalPaid += $paid;
                        $totalDue += $due;
                    @endphp
                    <td>{{ $row->voucher_no  }}</td>
                    <td>{{ $row->customer?->name }} - ({{ $row->customer?->phone }})</td>
                    <td>{{ $row->sale_date }}</td>
                    <td>
                        @foreach($row->items as $item)
                            {{ $item->product->name}} x {{ $item->qty}}({{ $item->unit->name}})<br>
                        @endforeach
                    </td>
                    <td>
                        <p class="mb-0 text-dark font-weight-bold">
                            Total Price : {{ adminCurrency() }} {{ number_format($row->price,2) }}
                        </p>
                        @if($row->vat)
                            <p class="mb-0">with {{ $row->vat }}% VAT</p>
                        @endif

                        <p class="mb-0 text-success font-weight-bold">Paid : {{ adminCurrency() }}{{ number_format($paid,2) }}</p>
                        <p class="mb-0 text-danger font-weight-bold">Due : {{ adminCurrency() }}{{ number_format($due,2) }}</p>
                    </td>

                {{-- Expense Report --}}
                @elseif($type == 'expense')
                    @php
                        $totalExpense += $row->amount;
                    @endphp
                    <td>{{ $row->date }}</td>
                    <td>{{ $row->name ?? '-' }}</td>
                    <td>{{ adminCurrency() }}{{ number_format($row->amount, 2) }}</td>
                @endif
            </tr>
        @endforeach
    </tbody>

    {{-- Table Footer --}}
    <tfoot class="table-light fw-bold">
        @if($type == 'purchase')
            <tr>
                <td colspan="3" class="text-end">Total :</td>
                <td>{{ adminCurrency() }}{{ number_format($totalPrice,2) }}</td>
                <td>{{ adminCurrency() }}{{ number_format($totalVat,2) }}</td>
                <td>{{ number_format($totalQty,2) }}</td>
                <td colspan="3"></td>
            </tr>
        @elseif($type == 'sale')
            <tr>
                <td colspan="4" class="text-end">Total :</td>
                <td>
                    <p class="mb-0 fw-bold">Total Price : {{ adminCurrency() }}{{ number_format($totalSalePrice,2) }}</p>
                    <p class="mb-0 text-success fw-bold">Paid : {{ adminCurrency() }}{{ number_format($totalPaid,2) }}</p>
                    <p class="mb-0 text-danger fw-bold">Due : {{ adminCurrency() }}{{ number_format($totalDue,2) }}</p>
                </td>
            </tr>
        @elseif($type == 'expense')
            <tr>
                <td colspan="2" class="text-end">Total :</td>
                <td>{{ adminCurrency() }}{{ number_format($totalExpense,2) }}</td>
            </tr>
        @endif
    </tfoot>
</table>

        </div>
    </div>
    @elseif(!empty($type))
    <div class="alert alert-warning">
        No {{ ucfirst($type) }} records found for selected date range.
    </div>
    @endif

</div>
@endsection