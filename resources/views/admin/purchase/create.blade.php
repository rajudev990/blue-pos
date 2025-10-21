@extends('admin.layouts.app')

@section('title')
Create Purchase
@endsection
@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Create Purchase</h3>
                        @can('view purchase')
                        <a href="{{ route('admin.purchases.index') }}" class="btn btn-success float-right"><i class="fa fa-angle-left"> Back</i></a>
                        @endcan
                    </div>
                    <!-- /.card-header -->
                    <form id="quickForm" method="POST" action="{{ route('admin.purchases.store') }}">
                        @csrf
                        <div class="card-body row">
                            <!-- Shop -->
                            <div class="form-group col-lg-6">
                                <label for="shop_id">Shop<span class="text-danger">*</span></label>
                                <select name="shop_id" id="shop_id" class="form-control @error('shop_id') is-invalid @enderror" required>
                                    <option value="">Select Shop</option>
                                    @foreach($shop as $item)
                                    <option value="{{ $item->id }}">{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('shop_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Product -->
                            <div class="form-group col-lg-6">
                                <label for="product_id">Product<span class="text-danger">*</span></label>
                                <select name="product_id" id="product_id" class="form-control @error('product_id') is-invalid @enderror" required>
                                    <option value="">Select Product</option>
                                </select>
                                @error('product_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Unit -->
                            <div class="form-group col-lg-6">
                                <label for="unit_id">Unit<span class="text-danger">*</span></label>
                                <select name="unit_id" id="unit_id" class="form-control @error('unit_id') is-invalid @enderror" required>
                                    <option value="">Select Unit</option>
                                </select>
                                @error('unit_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Purchase Date -->
                            <div class="form-group col-lg-6">
                                <label for="purchases_date">Purchase Date <span class="text-secondary">(Optional)</span></label>
                               <input type="date" name="purchases_date" id="purchases_date" class="form-control @error('purchases_date') is-invalid @enderror" value="{{ date('Y-m-d') }}">
                                @error('purchases_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Quantity -->
                            <div class="form-group col-lg-6">
                                <label for="qty">Quantity <span class="text-secondary">(Optional)</span></label>
                                <input type="number" name="qty" id="qty" class="form-control @error('qty') is-invalid @enderror">
                                @error('qty') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Buy Price -->
                            <div class="form-group col-lg-6">
                                <label for="buy_price">Buy Price({{ adminCurrency() }})<span class="text-danger">*</span></label>
                                <input type="number" name="buy_price" id="buy_price" class="form-control @error('buy_price') is-invalid @enderror" required>
                                @error('buy_price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- VAT -->
                            <div class="form-group col-lg-6">
                                <label for="vat">VAT({{ adminCurrency() }})<span class="text-secondary">(Optional)</span></label>
                                <input type="number" name="vat" id="vat" class="form-control @error('vat') is-invalid @enderror">
                                @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Final Price -->
                            <div class="form-group col-lg-6">
                                <label for="final_price">Final Price({{ adminCurrency() }})<span class="text-danger">*</span></label>
                                <input type="text" name="final_price" id="final_price" class="form-control @error('final_price') is-invalid @enderror" readonly>
                                @error('final_price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Per Product Price -->
                            <div class="form-group col-lg-6">
                                <label for="per_product_price">Per Product Price({{ adminCurrency() }})<span class="text-danger">*</span></label>
                                <input type="text" name="per_product_price" id="per_product_price" class="form-control @error('per_product_price') is-invalid @enderror" readonly>
                                @error('per_product_price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Status -->
                            <!-- <div class="form-group col-lg-6">
            <label for="status">Status </label>
            <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                <option value="1">Active</option>
                <option value="0">Deactive</option>
            </select>
            @error('status') <span class="text-danger">{{ $message }}</span> @enderror
        </div> -->
                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-info"><i class="fa fa-save"></i> Save</button>
                        </div>
                    </form>

                    <!-- /.card-body -->
                </div>
            </div>
        </div>
    </div>
</section>

@endsection

@section('script')
<script>
    $(document).ready(function() {

        function calculate() {
            let qty = parseFloat($("#qty").val()) || 0;
            let buyPrice = parseFloat($("#buy_price").val()) || 0;
            let vat = parseFloat($("#vat").val()) || 0;

            let finalPrice = buyPrice + vat;
            let perProductPrice = qty > 0 ? (finalPrice / qty) : finalPrice;

            $("#final_price").val(finalPrice.toFixed(2));
            $("#per_product_price").val(perProductPrice.toFixed(2));
        }

        // Live calculation
        $("#qty, #buy_price, #vat").on("input", calculate);
        $("#buy_price, #vat").on("blur", function() {
            let val = parseFloat($(this).val()) || 0;
            $(this).val(val.toFixed(2));
            calculate();
        });

        // Shop select → load products & units via AJAX
        $("#shop_id").change(function() {
            let shop_id = $(this).val();

            if (shop_id) {
                $.ajax({
                    url: '/admin/shop-products-units/' + shop_id, // route to fetch products & units
                    type: 'GET',
                    dataType: 'json',
                    success: function(res) {
                        $('#product_id').empty().append('<option value="">Select Product</option>');
                        $('#unit_id').empty().append('<option value="">Select Unit</option>');

                        $.each(res.products, function(key, value) {
                            $('#product_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });

                        $.each(res.units, function(key, value) {
                            $('#unit_id').append('<option value="' + value.id + '">' + value.name + '</option>');
                        });
                    }
                });
            } else {
                $('#product_id').empty().append('<option value="">Select Product</option>');
                $('#unit_id').empty().append('<option value="">Select Unit</option>');
            }
        });

    });
</script>

@endsection