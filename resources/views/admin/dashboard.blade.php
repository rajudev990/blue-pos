@extends('admin.layouts.app')

@section('title')
Dashboard
@endsection


@section('content')

<div class="content-header">
    <div class="container-fluid">
        <div class="row mb-2">
            <div class="col-sm-6">
                <h1 class="m-0">Dashboard</h1>
            </div>
            <div class="col-sm-6">
                <ol class="breadcrumb float-sm-right">
                    <li class="breadcrumb-item"><a href="{{ route('admin.dashboard') }}">Home</a></li>
                    <li class="breadcrumb-item active">Dashboard</li>
                </ol>
            </div>
        </div>
    </div>
</div>


@php 
$shops = App\Models\Shop::count();
$employes = App\Models\User::count();
$products = App\Models\Product::count();
$customers = App\Models\Customer::count();
$purchases = App\Models\Purchase::sum('final_price');
$sales = App\Models\Sale::sum('price');
$payments = App\Models\Payment::sum('paid');
$due = $sales - $payments;
$expenses = App\Models\Expense::sum('amount');
@endphp

<section class="content">
    <div class="container-fluid">
        <div class="row">

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ $shops }}</h3>
                        <p>Total Shop</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ $employes }}</h3>
                        <p>Total Employes</p>
                    </div>
                </div>
            </div>


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
                        <h3>{{ adminCurrency() }}{{ $purchases }}</h3>
                        <p>Total Purchase</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-primary">
                    <div class="inner">
                        <h3>{{ adminCurrency() }}{{ $sales }}</h3>
                        <p>Total Sales</p>
                    </div>
                </div>
            </div>

            <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-info">
                    <div class="inner">
                        <h3>{{ adminCurrency() }}{{ $payments }}</h3>
                        <p>Total Payments</p>
                    </div>
                </div>
            </div>

             <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ adminCurrency() }}{{ number_format($due,2) }}</h3>
                        <p>Total Due</p>
                    </div>
                </div>
            </div>

             <div class="col-lg-3 col-6">
                <!-- small box -->
                <div class="small-box bg-dark">
                    <div class="inner">
                        <h3>{{ adminCurrency() }}{{ $expenses }}</h3>
                        <p>Total Expense</p>
                    </div>
                </div>
            </div>

        </div>
    </div>
</section>
@endsection