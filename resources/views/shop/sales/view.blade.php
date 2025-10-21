@extends('shop.layouts.app')

@section('title')
Edit Sale
@endsection

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

                    <form action="{{ route('shop.sales.update', $sale->id) }}" method="POST" id="quickForm">
                        @csrf
                        @method('PUT')

                        <div class="card-body row">

                            <input type="hidden" name="shop_id" value="{{ $shop->id }}">

                            <!-- Customer -->
                            <div class="form-group col-lg-4">
                                <label for="customer_id">Customer<span class="text-danger">*</span></label>
                                <select name="customer_id" id="customer_id" class="form-control select2" required data-placeholder="Select Customer">
                                    <option value="" disabled>Select Customer</option>
                                    @foreach($customers as $customer)
                                        <option value="{{ $customer->id }}" {{ $sale->customer_id == $customer->id ? 'selected' : '' }}>
                                            {{ $customer->name }} - ({{ $customer->phone }})
                                        </option>
                                    @endforeach
                                </select>
                                @error('customer_id') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Voucher -->
                            <div class="form-group col-lg-4">
                                <label for="voucher_no">Voucher No<span class="text-danger">*</span></label>
                                <input type="text" name="voucher_no" id="voucher_no" class="form-control" required value="{{ $sale->voucher_no }}">
                                <small id="voucher-error" class="text-danger d-none"></small>
                                @error('voucher_no') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                            <!-- Sale Date -->
                            <div class="form-group col-lg-4">
                                <label for="sale_date">Sale Date</label>
                                <input type="date" name="sale_date" id="sale_date" class="form-control" value="{{ $sale->sale_date }}">
                                @error('sale_date') <span class="text-danger">{{ $message }}</span> @enderror
                            </div>

                        </div>

                        <!-- Sale Items -->
                        <div class="card-body">
                            <h5>Sale Items</h5>
                            <table class="table table-bordered" id="saleItemsTable">
                                <thead>
                                    <tr>
                                        <th style="width: 25%;">Product</th>
                                        <th style="width: 25%;">Unit</th>
                                        <th>Quantity</th>
                                        <th>Price</th>
                                        <th>
                                            <button type="button" class="btn btn-success btn-sm" id="addRow"><i class="fa fa-plus"></i></button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($sale->items as $index => $item)
                                    <tr>
                                        <td>
                                            <select name="items[{{ $index }}][product_id]" class="form-control select2" required>
                                                <option value="" disabled>Select Product</option>
                                                @foreach($products as $product)
                                                <option value="{{ $product->id }}" {{ $item->product_id == $product->id ? 'selected' : '' }}>
                                                    {{ $product->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td>
                                            <select name="items[{{ $index }}][unit_id]" class="form-control select2" required>
                                                <option value="" disabled>Select Unit</option>
                                                @foreach($units as $unit)
                                                <option value="{{ $unit->id }}" {{ $item->unit_id == $unit->id ? 'selected' : '' }}>
                                                    {{ $unit->name }}
                                                </option>
                                                @endforeach
                                            </select>
                                        </td>
                                        <td><input type="number" name="items[{{ $index }}][qty]" class="form-control" required value="{{ $item->qty }}"></td>
                                        <td><input type="number" step="0.01" name="items[{{ $index }}][price]" class="form-control price-input" required value="{{ $item->price }}"></td>
                                        <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- VAT and Total -->
                        <div class="card-body row">
                            <div class="form-group col-lg-2">
                                <label for="vat">VAT (%)</label>
                                <input type="number" step="0.01" name="vat" id="vat" class="form-control" value="{{ $sale->vat }}">
                            </div>
                            <div class="form-group col-lg-2">
                                <label for="price">Total Price</label>
                                <input type="text" name="price" id="price" class="form-control" readonly>
                            </div>
                            <div class="form-group col-lg-2">
                                <label for="paid">Paid</label>
                                <input type="text" name="paid" id="paid" class="form-control" value="{{ number_format($sale->payments->sum('paid'), 2, '.', '') }}">
                            </div>
                            <div class="form-group col-lg-2">
                                <label for="due">Due</label>
                                <input type="text" name="due" id="due" class="form-control" readonly>
                            </div>
                            <div class="form-group col-lg-2">
                                <label for="payment_by">Payment By</label>
                                <select name="payment_by" id="payment_by" class="form-control">
                                    <option value="Cash" {{ $sale->payments->first()?->payment_by == 'Cash' ? 'selected' : '' }}>Cash</option>
                                    <option value="Bank" {{ $sale->payments->first()?->payment_by == 'Bank' ? 'selected' : '' }}>Bank</option>
                                </select>
                            </div>
                            <div class="col-lg-2 form-group mt-2">
                                <a href="{{ route('shop.sales.index') }}" class="btn btn-info mt-4 w-100"><i class="fa fa-back"></i> Back</a>
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
$(document).ready(function() {

    let rowIndex = {{ $sale->items->count() }};

    function calculateTotal() {
        let total = 0;
        $("#saleItemsTable tbody tr").each(function() {
            let qty = parseFloat($(this).find("input[name*='[qty]']").val()) || 0;
            let price = parseFloat($(this).find("input[name*='[price]']").val()) || 0;
            total += qty * price;
        });

        let vat = parseFloat($("#vat").val()) || 0;
        total += (total * vat / 100);

        $("#price").val(total.toFixed(2));

        let paid = parseFloat($("#paid").val()) || 0;
        if (paid > total) {
            alert("Paid amount cannot be greater than Total Price!");
            paid = total;
            $("#paid").val(total.toFixed(2));
        }

        let due = total - paid;
        $("#due").val(due.toFixed(2));
    }

    calculateTotal();

    // Add new row
    $("#addRow").click(function() {
        let newRow = `<tr>
            <td>
                <select name="items[${rowIndex}][product_id]" class="form-control select2" required>
                    <option value="" disabled selected>Select Product</option>
                    @foreach($products as $product)
                        <option value="{{ $product->id }}">{{ $product->name }}</option>
                    @endforeach
                </select>
            </td>
            <td>
                <select name="items[${rowIndex}][unit_id]" class="form-control select2" required>
                    <option value="" disabled selected>Select Unit</option>
                    @foreach($units as $unit)
                        <option value="{{ $unit->id }}">{{ $unit->name }}</option>
                    @endforeach
                </select>
            </td>
            <td><input type="number" name="items[${rowIndex}][qty]" class="form-control" required></td>
            <td><input type="number" step="0.01" name="items[${rowIndex}][price]" class="form-control price-input" required></td>
            <td><button type="button" class="btn btn-danger btn-sm removeRow"><i class="fa fa-trash"></i></button></td>
        </tr>`;
        $("#saleItemsTable tbody").append(newRow);
        $("#saleItemsTable tbody tr:last .select2").select2({width:'100%'});
        rowIndex++;
    });

    // Remove row
    $(document).on("click", ".removeRow", function() {
        $(this).closest("tr").remove();
        calculateTotal();
    });

    // Input changes
    $(document).on("input", "#saleItemsTable input, #vat, #paid", function() {
        calculateTotal();
    });

    $(document).on("blur", ".price-input, #paid", function() {
        let val = parseFloat($(this).val()) || 0;
        $(this).val(val.toFixed(2));
        calculateTotal();
    });

    // Custom validation for select2
    $("#quickForm").on("submit", function(e) {
        let valid = true;
        $("#quickForm select[required]").each(function() {
            let $this = $(this);
            if (!$this.val()) {
                valid = false;
                if ($this.hasClass("select2-hidden-accessible")) {
                    $this.next(".select2").find(".select2-selection").addClass("border border-danger");
                }
            } else {
                if ($this.hasClass("select2-hidden-accessible")) {
                    $this.next(".select2").find(".select2-selection").removeClass("border border-danger");
                }
            }
        });

        if (!valid) {
            e.preventDefault();
            alert("Please fill all required select fields!");
        }
    });

    $(document).on("change", "#quickForm select[required]", function() {
        if ($(this).val() && $(this).hasClass("select2-hidden-accessible")) {
            $(this).next(".select2").find(".select2-selection").removeClass("border border-danger");
        }
    });
});
</script>

<style>
.select2-selection.border-danger { border: 1px solid red !important; }
</style>
@endsection
