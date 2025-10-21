@extends('shop.layouts.app')

@section('title')
Dashboard
@endsection


@section('shop')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('shop.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>

@php 
$shopId = auth()->guard('shop')->user()->id;
$products = App\Models\Product::where('shop_id',$shopId)->count();
$customers = App\Models\Customer::where('shop_id',$shopId)->count();
$purchases = App\Models\Purchase::where('shop_id',$shopId)->sum('final_price');
$sales = App\Models\Sale::where('shop_id',$shopId)->sum('price');
$payments = App\Models\Payment::where('shop_id',$shopId)->sum('paid');
$expenses = App\Models\Expense::where('shop_id',$shopId)->sum('amount');
$due = $sales - $payments;
@endphp

<section class="content">
    <div class="container-fluid">
        <div class="row">

            


            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ $products }}</h3>
                        <p>Total Products</p>
                    </div>
                </div>
            </div>


            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ $customers }}</h3>
                        <p>Total Customers</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ shopCurrency() }}{{ $purchases }}</h3>
                        <p>Total Purchase</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ shopCurrency() }}{{ $sales }}</h3>
                        <p>Total Sales</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ shopCurrency() }}{{ $payments }}</h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>

             <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ shopCurrency() }}{{ number_format($due,2) }}</h3>
                        <p>Total Due</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-secondary">
                    <div class="inner">
                        <h3>{{ shopCurrency() }}{{ $expenses }}</h3>
                        <p>Total Expense</p>
                    </div>
                </div>
            </div>


        </div>
    </div>
</section>

@endsection