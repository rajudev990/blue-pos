@extends('admin.layouts.app')

@section('title')
Purchase List
@endsection
@section('content')
<section class="content pt-4">
    <div class="container-fluid">
        <div class="row">
            <div class="col-md-12">
                <div class="card card-cyan">
                    <div class="card-header">
                        <h3 class="card-title">Purchase List</h3>
                        @can('create purchase')
                        <a href="{{ route('admin.purchases.create') }}" class="btn btn-success float-right"><i class="fa fa-plus"></i> Add</a>
                        @endcan
                    </div>
                    <!-- /.card-header -->
                    <div class="card-body">
                        <table id="example1" class="table table-bordered table-striped">
                            <thead>
                                <tr>
                                    <th>Sl</th>
                                    <th>Shop Name</th>
                                    <th>Product</th>
                                    <th>Date</th>
                                    <th>Price({{ adminCurrency() }})</th>
                                    <th>Vat</th>
                                    <th>Qty</th>
                                    <th>Per Price({{ adminCurrency() }})</th>
                                    <th>Stock</th>
                                    <th>Total Sales({{ adminCurrency() }})</th>
                                    <th class="text-center">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($data as $item)
                                @can('view purchase')
                                <tr>
                                    <td>{{ $loop->iteration }}</td>
                                    <td>{{ $item->shop?->name }}</td>
                                    <td>{{ $item->product?->name }} ({{ $item->unit?->name }})</td>
                                    <td>
                                        {{ $item->purchases_date }}
                                    </td>
                                    <td>
                                        {{ adminCurrency() }}{{ number_format($item->buy_price,2) }}
                                    </td>
                                    <td>
                                        {{ adminCurrency() }}{{ number_format($item->vat,2) }}
                                    </td>
                                    <td>
                                        {{ number_format($item->qty,2) }}
                                    </td>
                                    <td>
                                        {{ adminCurrency() }}{{ number_format(($item->buy_price + $item->vat) / $item->qty,2) }}
                                    </td>
                                    <td>
                                        {{ $item->stock() }} ({{ $item->unit?->name }})
                                    </td>
                                    <td>
                                        {{ $item->saleCount() }}
                                    </td>

                                    <td class="text-center">
                                        @can('edit purchase')
                                        @if($item->saleCount() == 0)
                                        <a href="{{ route('admin.purchases.edit', $item->id) }}" class="btn btn-sm btn-primary"><i class="fa fa-edit"></i></a>

                                        <form id="delete-form-{{ $item->id }}" action="{{ route('admin.purchases.destroy', $item->id) }}" method="POST" style="display: none;">
                                            @csrf @method('DELETE')
                                        </form>
                                        @can('delete purchase')
                                        <a href="#" class="btn btn-sm btn-danger delete-btn" data-id="{{ $item->id }}"><i class="fa fa-trash"></i></a>
                                        @endcan
                                        @endif
                                        @endcan
                                    </td>

                                </tr>
                                @endcan
                                @endforeach
                            </tbody>
                        </table>
                    </div>
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

        // Event delegation দিয়ে status toggle handle করা
        $(document).on('change', '.status-toggle', function() {
            let checkbox = $(this);
            let id = checkbox.data('id');
            let status = checkbox.is(':checked') ? 1 : 0;

            $.ajax({
                url: "{{ route('admin.purchases.status.update') }}",
                method: "POST",
                data: {
                    _token: "{{ csrf_token() }}",
                    id: id,
                    status: status
                },
                success: function(res) {
                    Swal.fire({
                        icon: res.status == 1 ? 'success' : 'warning',
                        title: res.status == 1 ? 'Status has been activated.' : 'Status has been deactivated.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                },
                error: function() {
                    Swal.fire({
                        icon: 'error',
                        title: 'There was a problem updating the status.',
                        showConfirmButton: false,
                        timer: 1500
                    });
                    checkbox.prop('checked', !status); // rollback
                }
            });
        });

    });
</script>
@endsection