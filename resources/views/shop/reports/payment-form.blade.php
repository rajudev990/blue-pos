@extends('shop.layouts.app')

@section('title', 'Payment')

@section('shop')
<div class="container pt-4">


    <div class="card shadow-sm mb-4">
        <div class="card-header bg-info text-white">
            <h5 class="mb-0"><i class="fa fa-chart-line"></i> Payment</h5>
        </div>
        <div class="card-body">
            <form id="quickForm" method="POST" action="{{ route('shop.payment.store') }}">
                @csrf
                <input type="hidden" name="sale_id" value="{{$data->id}}">
                <input type="hidden" name="customer_id" value="{{$data->customer->id}}">

                <div class="row">
                    <div class="form-group col-lg-6">
                        <label>Customer</label>
                        <input type="text" class="form-control" readonly value="{{ $data->customer->name }} - ({{ $data->customer->phone }})">
                    </div>

                    <div class="form-group col-lg-6">
                        <label>Sale Date</label>
                        <input type="date" class="form-control" readonly value="{{ $data->sale_date }}">
                    </div>

                    <div class="form-group col-lg-6">
                        <label>Total Price({{ shopCurrency() }})</label>
                        <input type="text" class="form-control" id="totalpaid" readonly value="{{ number_format($data->price,2) }}">
                    </div>

                    <div class="form-group col-lg-6">
                        <label>Total Paid({{ shopCurrency() }})</label>
                        <input type="text" class="form-control" id="totalpaid" readonly value="{{ number_format($data->payments->sum('paid'),2) }}">
                    </div>

                    <div class="form-group col-lg-6">
                        <label>Due({{ shopCurrency() }})</label>
                        <input type="text" class="form-control" id="due" readonly value="{{ number_format($data->price - $data->payments->sum('paid'),2) }}">
                    </div>

                    <div class="form-group col-lg-6">
                        <label>Paid({{ shopCurrency() }})<span class="text-danger">*</span></label>
                        <input type="number" step="0.01" name="paid" id="paid" class="form-control @error('paid') is-invalid @enderror" required>
                        @error('paid')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>


                    <div class="form-group col-lg-6">
                        <label>Received By <span class="text-danger">*</span></label>
                        <select name="payment_by" id="payment_by" class="form-control">
                            <option value="Cash">Cash</option>
                            <option value="Bank">Bank</option>
                        </select>
                        @error('payment_by')<span class="text-danger">{{ $message }}</span>@enderror
                    </div>

                </div>

                <div class="d-flex justify-content-end mt-3">
                    <button type="submit" class="btn btn-info"><i class="fa fa-money-bill"></i> Paid</button>
                </div>
            </form>
        </div>
    </div>



</div>
@endsection
@section('script')
<script>
const paidInput = document.getElementById('paid');
const dueInput = document.getElementById('due');

paidInput.addEventListener('blur', function() { // blur এ apply করা হলো, typing শেষ হলে
    let due = parseFloat(dueInput.value.replace(/,/g, '')) || 0;
    let paid = parseFloat(this.value) || 0;

    // prevent overpayment
    if (paid > due) {
        alert('Paid amount cannot exceed the due amount!');
        this.value = due.toFixed(2);
        return;
    }

    // যদি decimal না থাকে, last .00 যোগ করো
    if (!this.value.includes('.')) {
        this.value = parseFloat(this.value).toFixed(2);
    } else {
        // decimal দিলে 2 decimal পর্যন্ত format করা
        this.value = parseFloat(this.value).toFixed(2);
    }
});
</script>
@endsection
