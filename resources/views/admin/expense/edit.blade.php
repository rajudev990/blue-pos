@extends('admin.layouts.app')

@section('title')
Edit Expense
@endsection
@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-8 m-auto">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Edit Expense</h3>
                        <a href="{{ route('admin.expenses.index') }}" class="btn btn-success float-right"><i class="fa fa-angle-left"> Back</i></a>
                    </div>
                    <!-- /.card-header -->
                    <form id="quickForm" method="POST" action="{{ route('admin.expenses.update',$data->id) }}">
                        @csrf
                        @method('PUT')
                        <div class="card-body row">


                            <div class="form-group col-lg-12">
                                <label for="shop_id">Shop<span class="text-danger">*</span></label>
                                <select name="shop_id" id="shop_id" class="form-control select2 @error('shop_id') is-invalid @enderror">
                                    @foreach ($shops as $item)
                                    <option value="{{ $item->id }}" {{ $data->shop_id == $item->id ? 'selected' : '' }}>{{ $item->name }}</option>
                                    @endforeach
                                </select>
                                @error('shop_id') <span class="text-danger">{{$message}}</span> @enderror
                            </div>


                            <div class="form-group col-lg-12">
                                <label for="name">Name<span class="text-danger">*</span></label>
                                <input type="text" name="name" id="name" class="form-control @error('name') is-invalid @enderror" required value="{{$data->name}}">
                                @error('name') <span class="text-danger">{{$message}}</span> @enderror
                            </div>

                            <div class="form-group col-lg-12">
                                <label for="date">Date<span class="text-danger">*</span></label>
                                <input type="date" name="date" id="date" class="form-control @error('date') is-invalid @enderror" required value="{{$data->date}}">
                                @error('date') <span class="text-danger">{{$message}}</span> @enderror
                            </div>

                            <div class="form-group col-lg-12">
                                <label for="amount">Amount({{ adminCurrency() }})<span class="text-danger">*</span></label>
                                <input type="text" step="0.01" name="amount" id="amount"
                                    class="form-control @error('amount') is-invalid @enderror" required value="{{$data->amount}}">
                                @error('amount') <span class="text-danger">{{$message}}</span> @enderror
                            </div>

                            <div class="form-group col-lg-12">
                                <label for="status">Status </label>
                                <select name="status" id="status" class="form-control @error('status') is-invalid @enderror">
                                    <option value="1" {{ $data->status==1 ? 'selected' : '' }}>Active</option>
                                    <option value="0" {{ $data->status==0 ? 'selected' : '' }}>Deactive</option>
                                </select>
                                @error('status') <span class="text-danger">{{$message}}</span> @enderror
                            </div>
                        </div>

                        <div class="card-footer d-flex justify-content-end">
                            <button type="submit" class="btn btn-info"><i class="fa fa-edit"></i> Update</button>
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
    document.getElementById('amount').addEventListener('blur', function() {
        let val = this.value.trim();
        if (val !== "" && !isNaN(val)) {
            // যদি সংখ্যা কিন্তু decimal না থাকে
            if (!val.includes('.')) {
                this.value = val + ".00";
            } else {
                // যদি decimal থাকে কিন্তু দুই digit না হয়
                let parts = val.split(".");
                if (parts[1].length === 1) {
                    this.value = val + "0"; // 12.5 => 12.50
                }
            }
        }
    });
</script>
@endsection