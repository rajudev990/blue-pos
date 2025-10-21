@extends('admin.layouts.app')

@section('title') Create Sales @endsection

@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Create Sale</h3>
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

                    <form action="{{ route('admin.sales.store') }}" method="POST" id="quickForm">
                        @csrf
                        <div class="card-body row">
                            <div class="form-group col-lg-3">
                                <label for="shop_id">Shop<span class="text-danger">*</span></label>
                                <select name="shop_id" id="shop_id" class="form-control select2" required>
                                    <option value="" selected disabled>Select Shop</option>
                                    @foreach($shops as $s)
                                        <option value="{{ $s->id }}" {{ (old('shop_id', $selectedShopId ?? '') == $s->id) ? 'selected' : '' }}>
                                            {{ $s->name }}
                                        </option>
                                    @endforeach
                                </select>
                                @error('shop_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-3">
                                <label for="customer_id">Customer<span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required>
                                    <option value="" selected disabled>Select Customer</option>
                                    @if(isset($customers) && $customers->count())
                                        @foreach($customers as $c)
                                            <option value="{{ $c->id }}" {{ old('customer_id') == $c->id ? 'selected' : '' }}>
                                                {{ $c->name }} - ({{ $c->phone }})
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                                @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-3">
                                <label for="voucher_no">Voucher No<span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" id="voucher_no" class="form-control" required value="{{ old('voucher_no') }}">
                                <small id="voucher-error" class="text-danger d-none"></small>
                                @error('voucher_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-3">
                                <label for="sale_date">Sale Date</label>
                                <input type="date" name="sale_date" id="sale_date" class="form-control" value="{{ old('sale_date', date('Y-m-d')) }}">
                                @error('sale_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                        </div>

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
                                            <button type="button" class="btn btn-success btn-sm" id="addRow" disabled><i class="fa fa-plus"></i></button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    {{-- old items support --}}
                                    @php $oldItems = old('items', []); @endphp
                                    @if($oldItems)
                                        @foreach($oldItems as $i => $it)
                                            <tr>
                                                <td>
                                                    <select name="items[{{ $i }}][product_id]" class="form-control product-select" required>
                                                        <option value="" disabled {{ isset($it['product_id']) ? '' : 'selected' }}>Select Product</option>
                                                        {{-- products list will be populated by JS after shop select --}}
                                                    </select>
                                                </td>
                                                <td>
                                                    <select name="items[{{ $i }}][unit_id]" class="form-control unit-select" required>
                                                        <option value="" disabled {{ isset($it['unit_id']) ? '' : 'selected' }}>Select Unit</option>
                                                    </select>
                                                </td>
                                                <td>
                                                    <input type="number" name="items[{{ $i }}][qty]" class="form-control qty-input" min="1" required value="{{ $it['qty'] ?? '' }}">
                                                    <small class="text-muted stock-msg"></small>
                                                </td>
                                                <td>
                                                    <input type="number" step="0.01" name="items[{{ $i }}][price]" class="form-control price-input" required value="{{ $it['price'] ?? '' }}">
                                                </td>
                                                <td>
                                                    <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                                </td>
                                            </tr>
                                        @endforeach
                                    @else
                                        <tr>
                                            <td>
                                                <select name="items[0][product_id]" class="form-control product-select" required>
                                                    <option value="" disabled selected>Select Product</option>
                                                </select>
                                            </td>
                                            <td>
                                                <select name="items[0][unit_id]" class="form-control unit-select" required>
                                                    <option value="" disabled selected>Select Unit</option>
                                                </select>
                                            </td>
                                            <td>
                                                <input type="number" name="items[0][qty]" class="form-control qty-input" min="1" required>
                                                <small class="text-muted stock-msg"></small>
                                            </td>
                                            <td>
                                                <input type="number" step="0.01" name="items[0][price]" class="form-control price-input" required>
                                            </td>
                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endif
                                </tbody>
                            </table>
                        </div>

                        <div class="card-body row">
                            <div class="form-group col-lg-2">
                                <label for="vat">VAT{{ adminCurrency() }}(%)</label>
                                <input type="number" step="0.01" name="vat" id="vat" class="form-control" value="{{ old('vat') }}">
                                @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="price">Total Price({{ adminCurrency() }})</label>
                                <input type="text" name="price" id="price" class="form-control" readonly value="{{ old('price') }}">
                                @error('price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="paid">Paid({{ adminCurrency() }})</label>
                                <input type="number" step="0.01" name="paid" id="paid" class="form-control" required value="{{ old('paid') }}">
                                @error('paid') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="due">Due({{ adminCurrency() }})</label>
                                <input type="text" name="due" id="due" class="form-control" readonly value="{{ old('due') }}">
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="payment_by">Payment By</label>
                                <select name="payment_by" id="payment_by" class="form-control">
                                    <option value="Cash" {{ old('payment_by')=='Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Bank" {{ old('payment_by')=='Bank' ? 'selected' : '' }}>Bank</option>
                                </select>
                            </div>

                            <div class="col-lg-2 form-group mt-2">
                                <button type="submit" class="btn btn-info mt-4 w-100"><i class="fa fa-save"></i> Save</button>
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
    // initialize select2
    if ($.fn.select2) {
        $('.select2').select2({ width: '100%' });
    }

    // client-side mappings (will be loaded when shop selected)
    let productUnitStock = {}; // key: "product_unit" => qty
    let productUnits = {}; // product_id => [unit_ids]
    let productsList = []; // [{id,name}, ...]
    let unitsList = {}; // id => {id,name}

    // helper for unit name
    function getUnitName(id){
        return unitsList[id] ? unitsList[id].name : id;
    }

    // enable addRow only when customer & voucher ok
    function toggleAddRow(){
        let cust = $('#customer_id').val();
        let voucher = $('#voucher_no').val();
        let voucherErrorHidden = $('#voucher-error').hasClass('d-none');
        if(cust && voucher && voucherErrorHidden){
            $('#addRow').prop('disabled', false);
        } else {
            $('#addRow').prop('disabled', true);
        }
    }

    // SHOP change -> load customers + products/units/stock
    $('#shop_id').on('change', function(){
        let shopId = $(this).val();
        if(!shopId) return;

        // 1) load customers
        $.get("{{ url('admin/sales/get-customers') }}/" + shopId, function(data){
            $('#customer_id').empty().append('<option value="" disabled selected>Select Customer</option>');
            $.each(data, function(i, c){
                $('#customer_id').append(`<option value="${c.id}">${c.name} - (${c.phone})</option>`);
            });
            $('#customer_id').val(null).trigger('change');
        });

        // 2) load products/units/stock
        $.get("{{ url('admin/sales/get-shop-data') }}/" + shopId, function(res){
            // populate client-side maps
            productUnitStock = res.productUnitStock || {};
            productUnits = res.productUnits || {};
            productsList = res.products || [];
            unitsList = {};
            (res.units || []).forEach(u => unitsList[u.id] = u);

            // update all product selects in table
            let options = `<option value="" disabled selected>Select Product</option>`;
            productsList.forEach(p => options += `<option value="${p.id}">${p.name}</option>`);

            $('#saleItemsTable tbody .product-select').each(function(){
                let prev = $(this).val();
                $(this).html(options);
                if(prev) $(this).val(prev).trigger('change');
            });

            // for old unit selects, we'll trigger product change to populate units
            $('#saleItemsTable tbody .product-select').trigger('change');
        });

        // reset voucher error, re-check when typing
        $('#voucher-error').addClass('d-none').text('');
        toggleAddRow();
    });

    // voucher ajax check (shop-wise)
    $('#voucher_no').on('input', function(){
        let voucher = $(this).val();
        let shop = $('#shop_id').val();
        if(!voucher || !shop){
            $('#voucher-error').addClass('d-none').text('');
            toggleAddRow();
            return;
        }

        $.post("{{ route('admin.sales.checkVoucher') }}", {
            _token: "{{ csrf_token() }}",
            voucher_no: voucher,
            shop_id: shop
        }).done(function(res){
            if(res.exists){
                $('#voucher-error').removeClass('d-none').text('This voucher number already exists for selected shop!');
            } else {
                $('#voucher-error').addClass('d-none').text('');
            }
            toggleAddRow();
        }).fail(function(xhr){
            console.log('Voucher check failed', xhr.responseText);
        });
    });

    // When product is selected, populate unit dropdown for that product using client mapping
    $(document).on('change', '.product-select', function(){
        let row = $(this).closest('tr');
        let pid = $(this).val();
        let unitSelect = row.find('.unit-select');
        unitSelect.empty();
        unitSelect.append('<option value="" disabled selected>Select Unit</option>');

        if(productUnits[pid] && productUnits[pid].length){
            productUnits[pid].forEach(function(uid){
                let name = getUnitName(uid) || uid;
                unitSelect.append(`<option value="${uid}">${name}</option>`);
            });
        }
        unitSelect.trigger('change');
    });

    // Add Row
    let rowIndex = {{ old('items') ? count(old('items')) : 1 }};
    $('#addRow').click(function(){
        if(!$('#customer_id').val() || !$('#voucher_no').val()){
            alert('Please select customer and enter voucher number first.');
            return;
        }
        let productOptions = `<option value="" disabled selected>Select Product</option>`;
        productsList.forEach(p => productOptions += `<option value="${p.id}">${p.name}</option>`);

        let newRow = `<tr>
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-control product-select" required>${productOptions}</select>
            </td>
            <td>
                <select name="items[${rowIndex}][unit_id]" class="form-control unit-select" required>
                    <option value="" disabled selected>Select Unit</option>
                </select>
            </td>
            <td>
                <input type="number" name="items[${rowIndex}][qty]" class="form-control qty-input" min="1" required>
                <small class="text-muted stock-msg"></small>
            </td>
            <td>
                <input type="number" step="0.01" name="items[${rowIndex}][price]" class="form-control price-input" required>
            </td>
            <td>
                <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
            </td>
        </tr>`;
        $('#saleItemsTable tbody').append(newRow);
        if ($.fn.select2) {
            $('#saleItemsTable tbody tr:last select').select2({ width: '100%' });
        }
        rowIndex++;
    });

    // Remove row
    $(document).on('click', '.removeRow', function(){
        $(this).closest('tr').remove();
        calculateTotal();
    });

    // fetchAvailable: use client mapping first, else fallback ajax
    function fetchAvailable(shopId, productId, unitId, callback){
        if(!productId || !unitId || !shopId){
            callback(0); return;
        }
        let key = productId + '_' + unitId;
        if(typeof productUnitStock[key] !== 'undefined'){
            callback(parseInt(productUnitStock[key]));
            return;
        }

        // fallback to server
        $.post("{{ route('admin.sales.checkStock') }}", {
            _token: "{{ csrf_token() }}",
            shop_id: shopId,
            product_id: productId,
            unit_id: unitId
        }).done(function(res){
            callback(parseInt(res.available));
        }).fail(function(){
            callback(0);
        });
    }

    // On unit change or qty input -> check stock and clamp qty if needed
    $(document).on('change input', '.unit-select, .qty-input', function(){
        let row = $(this).closest('tr');
        let productId = row.find('.product-select').val();
        let unitId = row.find('.unit-select').val();
        let qtyField = row.find('.qty-input');
        let qty = parseFloat(qtyField.val()) || 0;
        let shopId = $('#shop_id').val();

        fetchAvailable(shopId, productId, unitId, function(avail){
            row.find('.stock-msg').text('Available: ' + avail);
            if(qty > avail){
                alert('Only ' + avail + ' available for this product/unit.');
                qtyField.val(avail);
            }
            calculateTotal();
        });
    });

    // price and paid blur formatting & recalc
    $(document).on('blur', '.price-input, #paid, #vat', function(){
        let val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(2));
        calculateTotal();
    });

    // calculate totals
    function calculateTotal(){
        let subtotal = 0;
        $('#saleItemsTable tbody tr').each(function(){
            let qty = parseFloat($(this).find("input[name*='[qty]']").val()) || 0;
            let price = parseFloat($(this).find("input[name*='[price]']").val()) || 0;
            subtotal += qty * price;
        });

        let vat = parseFloat($('#vat').val()) || 0;
        let total = subtotal + (subtotal * vat / 100);
        $('#price').val(total.toFixed(2));

        let paid = parseFloat($('#paid').val()) || 0;
        if(paid > total) {
            paid = total;
            $('#paid').val(paid.toFixed(2));
        }
        $('#due').val((total - paid).toFixed(2));
    }

    // initial calculate
    calculateTotal();

    // form submit client validation
    $('#quickForm').on('submit', function(e){
        if(!$('#shop_id').val()){
            alert('Please select a shop.');
            e.preventDefault(); return false;
        }
        if(!$('#customer_id').val()){
            alert('Please select a customer.');
            e.preventDefault(); return false;
        }
        if(!$('#voucher_no').val()){
            alert('Please enter voucher no.');
            e.preventDefault(); return false;
        }
        if($('#saleItemsTable tbody tr').length < 1){
            alert('Please add at least one item.');
            e.preventDefault(); return false;
        }

        let ok = true;
        $('#saleItemsTable tbody tr').each(function(){
            let product = $(this).find('.product-select').val();
            let unit = $(this).find('.unit-select').val();
            let qty = parseFloat($(this).find('.qty-input').val()) || 0;
            let price = parseFloat($(this).find('.price-input').val()) || 0;

            if(!product || !unit || qty <= 0 || price < 0){
                ok = false;
                $(this).find('select, input').addClass('border border-danger');
            } else {
                $(this).find('select, input').removeClass('border border-danger');
            }
        });

        if(!ok){
            alert('Please fill all item fields correctly.');
            e.preventDefault(); return false;
        }

        // ensure paid <= total
        let total = parseFloat($('#price').val()) || 0;
        let paid = parseFloat($('#paid').val()) || 0;
        if(paid > total){
            alert('Paid cannot be greater than total.');
            e.preventDefault(); return false;
        }
    });

    // remove red border on input/change
    $(document).on('change input', '#quickForm select, #quickForm input', function(){
        if($(this).val()){
            $(this).removeClass('border border-danger');
        }
    });

    // If there were old items (server-rendered), after shop load we need to set product options & units
    // Trigger product-change for each row so unit-population & stock update runs
    $('#saleItemsTable tbody tr').each(function(){
        $(this).find('.product-select').trigger('change');
    });

});
</script>

<style>
    .stock-msg { font-size: 12px; display: block; margin-top: 4px; }
    .border-danger { border: 1px solid red !important; }
</style>
@endsection
