@extends('shop.layouts.app')

@section('title', 'Due List')

@section('shop')
<div class="container pt-4">


    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa fa-chart-line"></i> Due List</h5>
        </div>
        <div class="card-body">
            <table class="table table-bordered">
                <thead>
                    <tr>
                        <th>Voucher No</th>
                        <th>Customer</th>
                        <th>Sale Date</th>
                        <th>Price</th>
                        <th>Total Paid</th>
                        <th>Due</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($sales as $sale)
                    <tr>
                        <td><a href="{{ route('shop.payment',$sale->id) }}">{{ $sale->voucher_no }}</a></td>
                        <td>{{ $sale->customer->name }} - ({{ $sale->customer->phone }})</td>
                        <td>{{ $sale->sale_date }}</td>
                        <td>{{ shopCurrency() }}{{ number_format($sale->price, 2) }}</td>
                        <td>{{ shopCurrency() }}{{ number_format($sale->total_paid, 2) }}</td>
                        <td>{{ shopCurrency() }}{{ number_format($sale->due, 2) }}</td>
                    </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>



</div>
@endsection