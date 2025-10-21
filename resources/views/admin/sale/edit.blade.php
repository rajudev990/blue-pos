@extends('admin.layouts.app')

@section('title') Edit Sale @endsection

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Edit Sale</h3>
                        <a href="{{ route('admin.sales.index') }}" class="btn btn-success float-right">
                            <i class="fa fa-angle-left"></i> Back
                        </a>
                    </div>

                    @if ($errors->any())
                    <div class="alert alert-danger">
                        <h5><i class="fa fa-exclamation-triangle"></i> Please fix the following errors:</h5>
                        <ul class="mb-0">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                    @endif

                    <form action="{{ route('admin.sales.update', $sale->id) }}" method="POST" id="quickForm">
                        @csrf
                        @method('PUT')

                        <div class="card-body row">
                            {{-- Shop --}}
                            <div class="form-group col-lg-3">
                                <label for="shop_id">Shop<span class="text-danger">*</span></label>
                                <select name="shop_id" id="shop_id" class="form-control select2" required>
                                    <option value="" disabled>Select Shop</option>
                                    @foreach($shops as $s)
                                        <option value="{{ $s->id }}" {{ ($sale->shop_id ?? '') == $s->id ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shop_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- Customer --}}
                            <div class="form-group col-lg-3">
                                <label for="customer_id">Customer<span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required>
                                    <option value="" disabled>Select Customer</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" {{ ($sale->customer_id ?? '') == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} - ({{ $c->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- Voucher --}}
                            <div class="form-group col-lg-3">
                                <label for="voucher_no">Voucher No<span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" id="voucher_no" class="form-control" required value="{{ old('voucher_no', $sale->voucher_no) }}">
                                <small id="voucher-error" class="text-danger d-none"></small>
                                @error('voucher_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            {{-- Sale Date --}}
                            <div class="form-group col-lg-3">
                                <label for="sale_date">Sale Date</label>
                                <input type="date" name="sale_date" id="sale_date" class="form-control" value="{{ old('sale_date', $sale->sale_date) }}">
                                @error('sale_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

                        {{-- Sale Items --}}
                        <div class="card-body">
                            <h5>Sale Items</h5>
                            <table class="table table-bordered" id="saleItemsTable">
                                <thead>
                                    <tr>
                                        <th style="width:30%;">Product</th>
                                        <th style="width:20%;">Unit</th>
                                        <th style="width:15%;">Qty</th>
                                        <th style="width:20%;">Price({{ adminCurrency() }})</th>
                                        <th style="width:10%;">
                                            <button type="button" class="btn btn-success btn-sm" id="addRow"><i class="fa fa-plus"></i></button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php
                                        $oldItems = old('items', $sale->items->map(fn($i)=>[
                                            'product_id'=>$i->product_id,
                                            'unit_id'=>$i->unit_id,
                                            'qty'=>$i->qty,
                                            'price'=>$i->price
                                        ])->toArray());
                                    @endphp

                                    @foreach($oldItems as $i => $it)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $i }}][product_id]" class="form-control product-select" required>
                                                <option value="" disabled>Select Product</option>
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $i }}][unit_id]" class="form-control unit-select" required>
                                                <option value="" disabled>Select Unit</option>
                                            </select>
                                        </td>
                                        <td>
                                            <input type="number" name="items[{{ $i }}][qty]" class="form-control qty-input" min="1" required value="{{ $it['qty'] }}">
                                            <small class="text-muted stock-msg"></small>
                                        </td>
                                        <td>
                                            <input type="number" step="0.01" name="items[{{ $i }}][price]" class="form-control price-input" required value="{{ $it['price'] }}">
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        {{-- Totals & Payment --}}
                        <div class="card-body row">
                            <div class="form-group col-lg-2">
                                <label for="vat">VAT{{ adminCurrency() }}(%)</label>
                                <input type="number" step="0.01" name="vat" id="vat" class="form-control" value="{{ old('vat', $sale->vat) }}">
                                @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="price">Total Price({{ adminCurrency() }})</label>
                                <input type="text" name="price" id="price" class="form-control" readonly value="{{ old('price', $sale->price) }}">
                                @error('price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="paid">Paid({{ adminCurrency() }})</label>
                                <input type="number" step="0.01" name="paid" id="paid" class="form-control" required value="{{ old('paid', number_format($sale->payments->sum('paid'), 2, '.', '')) }}">
                                @error('paid') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="due">Due({{ adminCurrency() }})</label>
                                <input type="text" name="due" id="due" class="form-control" readonly value="{{ old('due', optional($sale->payment)->paid ? ($sale->price - $sale->payment->paid) : $sale->price) }}">
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="payment_by">Payment By</label>
                                <select name="payment_by" id="payment_by" class="form-control">
                                    <option value="Cash" {{ (old('payment_by', optional($sale->payment)->payment_by)=='Cash') ? 'selected' : '' }}>Cash</option>
                                    <option value="Bank" {{ (old('payment_by', optional($sale->payment)->payment_by)=='Bank') ? 'selected' : '' }}>Bank</option>
                                </select>
                            </div>

                            <div class="col-lg-2 form-group mt-2">
                                <button type="submit" class="btn btn-info mt-4 w-100"><i class="fa fa-save"></i> Update</button>
                            </div>
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
$(function(){

    if ($.fn.select2) $('.select2').select2({ width: '100%' });

    let oldItems = @json($oldItems);
    let productsList = @json($products ?? []);
    let productUnits = @json($productUnits ?? []);
    let unitsList = {};
    @foreach($units ?? [] as $u)
        unitsList["{{ $u->id }}"] = {id:"{{ $u->id }}", name:"{{ $u->name }}"};
    @endforeach

    // Populate products in a row
    function populateProducts(row){
        let options = `<option value="" disabled selected>Select Product</option>`;
        productsList.forEach(p => options += `<option value="${p.id}">${p.name}</option>`);
        row.find('.product-select').html(options);
    }

    // Populate units in a row
    function populateUnits(row, productId, selectedUnit=null){
        let options = `<option value="" disabled selected>Select Unit</option>`;
        if(productId && productUnits[productId]){
            productUnits[productId].forEach(u => {
                let name = unitsList[u] ? unitsList[u].name : u;
                options += `<option value="${u}">${name}</option>`;
            });
        }
        row.find('.unit-select').html(options);
        if(selectedUnit) row.find('.unit-select').val(selectedUnit);
    }

    // Pre-select old items
    $('#saleItemsTable tbody tr').each(function(index){
        let row = $(this);
        populateProducts(row);

        let productVal = oldItems[index]?.product_id || '';
        let unitVal = oldItems[index]?.unit_id || '';

        if(productVal){
            row.find('.product-select').val(productVal);
            populateUnits(row, productVal, unitVal);
        }
    });

    // When shop changes
    $('#shop_id').on('change', function(){
        let shopId = $(this).val();
        if(!shopId) return;

        // Load customers
        $.get("{{ url('admin/sales/get-customers') }}/" + shopId, function(data){
            $('#customer_id').empty().append('<option value="" disabled selected>Select Customer</option>');
            data.forEach(c => $('#customer_id').append(`<option value="${c.id}">${c.name} - (${c.phone})</option>`));
        });

        // Load products + units
        $.get("{{ url('admin/sales/get-shop-data') }}/" + shopId, function(res){
            productsList = res.products || [];
            productUnits = res.productUnits || [];
            unitsList = {};
            (res.units || []).forEach(u => unitsList[u.id] = u);

            // Update all existing rows
            $('#saleItemsTable tbody tr').each(function(){
                let row = $(this);
                let prevProduct = row.find('.product-select').val();
                populateProducts(row);
                if(prevProduct) populateUnits(row, prevProduct, row.find('.unit-select').val());
            });
        });
    });

    // Product change => populate units
    $(document).on('change', '.product-select', function(){
        let row = $(this).closest('tr');
        let productId = $(this).val();
        populateUnits(row, productId);
    });

    // Add Row
    $('#addRow').click(function(){
        let i = $('#saleItemsTable tbody tr').length;
        let row = $(`
            <tr>
                <td><select name="items[${i}][product_id]" class="form-control product-select" required></select></td>
                <td><select name="items[${i}][unit_id]" class="form-control unit-select" required></select></td>
                <td><input type="number" name="items[${i}][qty]" class="form-control qty-input" min="1" required><small class="text-muted stock-msg"></small></td>
                <td><input type="number" step="0.01" name="items[${i}][price]" class="form-control price-input" required></td>
                <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>
            </tr>
        `);
        $('#saleItemsTable tbody').append(row);
        populateProducts(row);

        // Product select on new row
        row.find('.product-select').on('change', function(){
            let pid = $(this).val();
            populateUnits(row, pid);
        });
    });

    // Remove row
    $(document).on('click', '.removeRow', function(){ 
        $(this).closest('tr').remove(); 
        recalcTotal();
    });

    // price and paid change formatting & recalc
    $(document).on('blur', '.price-input, #paid, #vat', function(){
        let val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(2));
        recalcTotal();
    });


    // Recalc total
    function recalcTotal(){
        let total = 0;
        $('#saleItemsTable tbody tr').each(function(){
            let qty = parseFloat($(this).find('.qty-input').val()) || 0;
            let price = parseFloat($(this).find('.price-input').val()) || 0;
            total += qty * price;
        });
        let vat = parseFloat($('#vat').val()) || 0;
        total = total + (total * vat / 100);
        $('#price').val(total.toFixed(2));

        let paid = parseFloat($('#paid').val()) || 0;
        $('#due').val((total - paid).toFixed(2));
    }

    $(document).on('input', '.qty-input, .price-input', recalcTotal);
    $('#vat, #paid').on('input', recalcTotal);

});
</script>

@endsection
