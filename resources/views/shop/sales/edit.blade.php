@extends('shop.layouts.app')

@section('title') Edit Sale @endsection

@section('shop')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Edit Sale</h3>
                        <a href="{{ route('shop.sales.index') }}" class="btn btn-success float-right">
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


                    <form action="{{ route('shop.sales.update', $sale->id) }}" method="POST" id="quickForm">
                        @csrf
                        @method('PUT')
                        <div class="card-body row">
                            <input type="hidden" name="shop_id" value="{{ $shop->id }}">

                            <div class="form-group col-lg-4">
                                <label for="customer_id">Customer<span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required>
                                    <option value="" selected disabled>Select Customer</option>
                                    @foreach($customers as $c)
                                        <option value="{{ $c->id }}" {{ $sale->customer_id == $c->id ? 'selected' : '' }}>
                                            {{ $c->name }} - ({{ $c->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-4">
                                <label for="voucher_no">Voucher No<span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" id="voucher_no" class="form-control" required value="{{ old('voucher_no', $sale->voucher_no) }}">
                                <small id="voucher-error" class="text-danger d-none"></small>
                                @error('voucher_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-4">
                                <label for="sale_date">Sale Date</label>
                                <input type="date" name="sale_date" id="sale_date" class="form-control" value="{{ old('sale_date', $sale->sale_date) }}">
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
                                        <th style="width:20%;">Price({{ shopCurrency() }})</th>
                                        <th style="width:10%;">
                                            <button type="button" class="btn btn-success btn-sm" id="addRow"><i class="fa fa-plus"></i></button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @php $items = old('items', $sale->items->toArray()); @endphp
                                    @foreach($items as $i => $it)
                                        <tr>
                                            <td>
                                                <select name="items[{{ $i }}][product_id]" class="form-control product-select" required>
                                                    <option value="" disabled>Select Product</option>
                                                    @foreach($products as $p)
                                                        <option value="{{ $p->id }}" {{ ($it['product_id'] ?? '') == $p->product_id ? 'selected' : '' }}>
                                                            {{ $p->name }}
                                                        </option>
                                                    @endforeach
                                                </select>
                                                @error("items.$i.product_id") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>

                                            <td>
                                                <select name="items[{{ $i }}][unit_id]" class="form-control unit-select" required>
                                                    <option value="" disabled>Select Unit</option>
                                                    @if(isset($productUnits[$it['product_id'] ?? 0]))
                                                        @foreach($productUnits[$it['product_id']] as $uId)
                                                            @php $u = \App\Models\Unit::find($uId); @endphp
                                                            @if($u)
                                                                <option value="{{ $u->id }}" {{ ($it['unit_id'] ?? '') == $u->id ? 'selected' : '' }}>
                                                                    {{ $u->name }}
                                                                </option>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error("items.$i.unit_id") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>

                                            <td>
                                                <input type="number" name="items[{{ $i }}][qty]" class="form-control qty-input" min="1" required value="{{ $it['qty'] ?? '' }}">
                                                <small class="text-muted stock-msg"></small>
                                                @error("items.$i.qty") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>

                                            <td>
                                                <input type="number" step="0.01" name="items[{{ $i }}][price]" class="form-control price-input" required value="{{ $it['price'] ?? '' }}">
                                                @error("items.$i.price") <span class="text-danger">{{ $message }}</span> @enderror
                                            </td>

                                            <td>
                                                <button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <div class="card-body row">
                            <div class="form-group col-lg-2">
                                <label for="vat">VAT({{ shopCurrency() }})(%)</label>
                                <input type="number" step="0.01" name="vat" id="vat" class="form-control" value="{{ old('vat', $sale->vat) }}">
                                @error('vat') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="price">Total Price({{ shopCurrency() }})</label>
                                <input type="text" name="price" id="price" class="form-control" readonly value="{{ old('price', $sale->price) }}">
                                @error('price') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>
                
                            <div class="form-group col-lg-2">
                                <label for="paid">Paid({{ shopCurrency() }})</label>
                                <input type="number" step="0.01" name="paid" id="paid" class="form-control" required
                                    value="{{ old('paid', number_format($sale->payments->sum('paid'), 2, '.', '')) }}">
                                @error('paid') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="due">Due({{ shopCurrency() }})</label>
                                <input type="text" name="due" id="due" class="form-control" readonly value="{{ old('due', $sale->price - $sale->payments->sum('paid')) }}">
                            </div>

                            <div class="form-group col-lg-2">
                                <label for="payment_by">Payment By</label>
                                <select name="payment_by" id="payment_by" class="form-control">
                                    <option value="Cash" {{ old('payment_by', $sale->payments->first()->payment_by ?? '')=='Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Bank" {{ old('payment_by', $sale->payments->first()->payment_by ?? '')=='Bank' ? 'selected' : '' }}>Bank</option>
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
    let productUnitStock = @json($productUnitStock);
    let productUnits = @json($productUnits);
    let unitsList = @json($units->keyBy('id')->map(fn($u)=>['id'=>$u->id,'name'=>$u->name]));

    function getUnitName(id){ return unitsList[id] ? unitsList[id].name : id; }

    if ($.fn.select2) $('.select2').select2({ width:'100%' });

    function toggleAddRow(){
        let cust = $('#customer_id').val();
        let voucher = $('#voucher_no').val();
        let voucherErrorHidden = $('#voucher-error').hasClass('d-none');
        $('#addRow').prop('disabled', !(cust && voucher && voucherErrorHidden));
    }
    $('#customer_id,#voucher_no').on('change input', toggleAddRow);

    $('#voucher_no').on('input', function(){
        let voucher = $(this).val();
        let shop = $('input[name="shop_id"]').val();
        if(!voucher){ $('#voucher-error').addClass('d-none').text(''); toggleAddRow(); return; }
        $.post("{{ route('shop.sales.checkVoucher') }}", {_token:"{{ csrf_token() }}", voucher_no:voucher, shop_id:shop})
        .done(function(res){
            $('#voucher-error').toggleClass('d-none', !res.exists).text(res.exists ? 'This voucher number already exists!' : '');
            toggleAddRow();
        });
    });

    $(document).on('change', '.product-select', function(){
        let row = $(this).closest('tr');
        let pid = $(this).val();
        let unitSelect = row.find('.unit-select');
        unitSelect.empty().append('<option value="" disabled selected>Select Unit</option>');
        if(productUnits[pid]) productUnits[pid].forEach(uid => unitSelect.append(`<option value="${uid}">${getUnitName(uid)}</option>`));
        unitSelect.trigger('change');
    });

    let rowIndex = $('#saleItemsTable tbody tr').length;
    $('#addRow').click(function(){
        if(!$('#customer_id').val() || !$('#voucher_no').val()){ alert('Please select customer and enter voucher number first.'); return; }
        let newRow = `<tr>
            <td><select name="items[${rowIndex}][product_id]" class="form-control product-select" required><option value="" disabled selected>Select Product</option>@foreach($products as $p)<option value="{{ $p->id }}">{{ $p->name }}</option>@endforeach</select></td>
            <td><select name="items[${rowIndex}][unit_id]" class="form-control unit-select" required><option value="" disabled selected>Select Unit</option></select></td>
            <td><input type="number" name="items[${rowIndex}][qty]" class="form-control qty-input" min="1" required><small class="text-muted stock-msg"></small></td>
            <td><input type="number" step="0.01" name="items[${rowIndex}][price]" class="form-control price-input" required></td>
            <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $('#saleItemsTable tbody').append(newRow);
        if ($.fn.select2) $('#saleItemsTable tbody tr:last select').select2({ width: '100%' });
        rowIndex++;
    });

    $(document).on('click', '.removeRow', function(){ $(this).closest('tr').remove(); calculateTotal(); });

    function fetchAvailable(productId, unitId, callback){
        if(!productId || !unitId){ callback(0); return; }
        let key = productId + '_' + unitId;
        if(typeof productUnitStock[key]!=='undefined'){ callback(parseInt(productUnitStock[key])); return; }
        $.post("{{ route('shop.sales.checkStock') }}", {_token:"{{ csrf_token() }}", product_id:productId, unit_id:unitId})
        .done(res=>callback(parseInt(res.available))).fail(()=>callback(0));
    }

    $(document).on('change input', '.unit-select, .qty-input', function(){
        let row = $(this).closest('tr');
        let productId = row.find('.product-select').val();
        let unitId = row.find('.unit-select').val();
        let qtyField = row.find('.qty-input');
        let stockMsg = row.find('.stock-msg');

        fetchAvailable(productId, unitId, function(stock){
            let qty = parseInt(qtyField.val() || 0);
            if(qty>stock){ qtyField.val(stock); stockMsg.text(`Only ${stock} available`).show(); } else { stockMsg.hide(); }
            calculateTotal();
        });
    });

    function calculateTotal(){
        let total=0;
        $('#saleItemsTable tbody tr').each(function(){
            let qty = parseFloat($(this).find('.qty-input').val()||0);
            let price = parseFloat($(this).find('.price-input').val()||0);
            total += qty*price;
        });
        let vat = parseFloat($('#vat').val()||0);
        total += total*(vat/100);
        $('#price').val(total.toFixed(2));
        let paid = parseFloat($('#paid').val()||0);
        $('#due').val((total-paid).toFixed(2));
    }

    // price and paid change formatting & recalc
    $(document).on('blur', '.price-input, #paid, #vat', function(){
        let val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(2));
        calculateTotal();
    });

    $('#vat, #paid').on('input', calculateTotal);

    calculateTotal();
});


</script>
@endsection
