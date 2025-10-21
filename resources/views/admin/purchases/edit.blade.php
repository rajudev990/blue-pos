@extends('admin.layouts.app')

@section('title')
Edit Purchase
@endsection

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Edit Purchase</h3>
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-success float-right"><i class="fa fa-angle-left"> Back</i></a>
                    </div>

                    <form id="quickForm" method="POST" action="{{ route('admin.purchases.update', $purchase->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body row">

                            <!-- Shop -->
                            <div class="form-group col-lg-6">
                                <label for="shop_id">Shop<span class="text-danger">*</span></label>
                                <select name="shop_id" id="shop_id" class="form-control @error('shop_id') is-invalid @enderror" required>
                                    <option value="">Select Shop</option>
                                    @foreach($shops as $shop)
                                    <option value="{{ $shop->id }}" {{ $purchase->shop_id == $shop->id ? 'selected' : '' }}>{{ $shop->name }}</option>
                                    @endforeach
                                </select>
                                @error('shop_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Product -->
                            <div class="form-group col-lg-6">
                                <label for="product_id">Product<span class="text-danger">*</span></label>
                                <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                    <option value="">Select Product</option>
                                    @foreach($products as $product)
                                    <option value="{{ $product->id }}" {{ $purchase->product_id == $product->id ? 'selected' : '' }}>{{ $product->name }}</option>
                                    @endforeach
                                </select>
                                @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Unit -->
                            <div class="form-group col-lg-6">
                                <label for="unit_id">Unit<span class="text-danger">*</span></label>
                                <select name="unit_id" id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" required>
                                    <option value="">Select Unit</option>
                                    @foreach($units as $unit)
                                    <option value="{{ $unit->id }}" {{ $purchase->unit_id == $unit->id ? 'selected' : '' }}>{{ $unit->name }}</option>
                                    @endforeach
                                </select>
                                @error('unit_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Purchase Date -->
                            <div class="form-group col-lg-6">
                                <label for="purchases_date">Purchase Date</label>
                                <input type="date" name="purchases_date" id="purchases_date" class="form-control @error('purchases_date') is-invalid @enderror" value="{{ $purchase->purchases_date ?? date('Y-m-d') }}">
                                @error('purchases_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Quantity -->
                            <div class="form-group col-lg-6">
                                <label for="qty">Quantity</label>
                                <input type="number" name="qty" id="qty" class="form-control @error('qty') is-invalid @enderror" value="{{ $purchase->qty }}">
                                @error('qty') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Buy Price -->
                            <div class="form-group col-lg-6">
                                <label for="buy_price">Buy Price({{ adminCurrency() }})</label>
                                <input type="number" name="buy_price" id="buy_price" class="form-control @error('buy_price') is-invalid @enderror" value="{{ $purchase->buy_price }}">
                                @error('buy_price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- VAT -->
                            <div class="form-group col-lg-6">
                                <label for="vat">VAT({{ adminCurrency() }})</label>
                                <input type="number" name="vat" id="vat" class="form-control @error('vat') is-invalid @enderror" value="{{ $purchase->vat }}">
                                @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Final Price -->
                            <div class="form-group col-lg-6">
                                <label for="final_price">Final Price({{ adminCurrency() }})</label>
                                <input type="text" name="final_price" id="final_price" class="form-control" readonly value="{{ $purchase->final_price }}">
                            </div>

                            <!-- Per Product Price -->
                            <div class="form-group col-lg-6">
                                <label for="per_product_price">Per Product Price({{ adminCurrency() }})</label>
                                <input type="text" name="per_product_price" id="per_product_price" class="form-control" readonly value="{{ $purchase->per_product_price }}">
                            </div>

                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-info"><i class="fa fa-edit"></i> Update</button>
                        </div>
                    </form>

                </div>
            </div>
        </div>
    </div>
</section>
@endsection

@section('script')
<script>
$(document).ready(function() {

    // =========================
    // Price Calculation Function
    // =========================
    function calculate() {
        let qty = parseFloat($("#qty").val()) || 0;
        let buyPrice = parseFloat($("#buy_price").val()) || 0;
        let vat = parseFloat($("#vat").val()) || 0;

        let finalPrice = buyPrice + vat;
        let perProductPrice = qty > 0 ? (finalPrice / qty) : finalPrice;

        $("#final_price").val(finalPrice.toFixed(2));
        $("#per_product_price").val(perProductPrice.toFixed(2));
    }

    // =========================
    // Trigger calculation
    // =========================
    $("#qty, #buy_price, #vat").on("input", calculate);
    $("#buy_price, #vat").on("blur", function() {
        let val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(2));
        calculate();
    });

    // =========================
    // Load products & units via AJAX
    // =========================
    $("#shop_id").change(function() {
        let shop_id = $(this).val();

        if (shop_id) {
            $.ajax({
                url: '/admin/shop-products-units/' + shop_id, // Route should return JSON { products: [...], units: [...] }
                type: 'GET',
                dataType: 'json',
                success: function(res) {

                    // Preserve old selection if exists
                    let oldProduct = "{{ $purchase->product_id ?? '' }}";
                    let oldUnit = "{{ $purchase->unit_id ?? '' }}";

                    $('#product_id').empty().append('<option value="">Select Product</option>');
                    $('#unit_id').empty().append('<option value="">Select Unit</option>');

                    // Append products
                    $.each(res.products, function(key, value) {
                        let selected = (oldProduct == value.id) ? 'selected' : '';
                        $('#product_id').append('<option value="' + value.id + '" ' + selected + '>' + value.name + '</option>');
                    });

                    // Append units
                    $.each(res.units, function(key, value) {
                        let selected = (oldUnit == value.id) ? 'selected' : '';
                        $('#unit_id').append('<option value="' + value.id + '" ' + selected + '>' + value.name + '</option>');
                    });

                }
            });
        } else {
            $('#product_id').empty().append('<option value="">Select Product</option>');
            $('#unit_id').empty().append('<option value="">Select Unit</option>');
        }
    });

    // =========================
    // Trigger shop change on page load (for edit)
    // =========================
    let currentShop = "{{ $purchase->shop_id ?? '' }}";
    if(currentShop) {
        $("#shop_id").val(currentShop).trigger('change');
    }

});
</script>

@endsection