@extends('shop.layouts.app')

@section('title', 'Stock Management')

@section('shop')
<div class="container pt-4">

    {{-- Buttons --}}
    <div class="btn-group mb-4" role="group">
        <button class="btn btn-outline-success active" onclick="showTable('inStock', this)">
            <i class="fa fa-box"></i> In Stock
        </button>
        <button class="btn btn-outline-warning" onclick="showTable('lowStock', this)">
            <i class="fa fa-exclamation-triangle"></i> Low Stock
        </button>
        <button class="btn btn-outline-danger" onclick="showTable('outOfStock', this)">
            <i class="fa fa-times-circle"></i> Out of Stock
        </button>
    </div>

    {{-- In Stock --}}
    <div id="inStock" class="stock-table">
        <h4 class="mb-3 text-success"><i class="fa fa-box"></i> In Stock Products</h4>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-success">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Stock Qty</th>
                        <th>Total Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($inStock as $stock)
                        <tr>
                            <td>{{ $stock['product']->name ?? '' }}</td>
                            <td>{{ $stock['unit']->name ?? '' }}</td>
                            <td><span class="badge bg-success">{{ $stock['qty'] }}</span></td>
                            <td><span class="badge bg-primary">{{ $stock['sold_qty'] ?? 0 }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No products available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Low Stock --}}
    <div id="lowStock" class="stock-table d-none">
        <h4 class="mb-3 text-warning"><i class="fa fa-exclamation-triangle"></i> Low Stock Products</h4>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-warning">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Stock Qty</th>
                        <th>Total Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lowStock as $stock)
                        <tr>
                            <td>{{ $stock['product']->name ?? '' }}</td>
                            <td>{{ $stock['unit']->name ?? '' }}</td>
                            <td><span class="badge bg-warning">{{ $stock['qty'] }}</span></td>
                            <td><span class="badge bg-primary">{{ $stock['sold_qty'] ?? 0 }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No products available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    {{-- Out of Stock --}}
    <div id="outOfStock" class="stock-table d-none">
        <h4 class="mb-3 text-danger"><i class="fa fa-times-circle"></i> Out of Stock Products</h4>
        <div class="table-responsive shadow-sm">
            <table class="table table-bordered table-striped align-middle">
                <thead class="table-danger">
                    <tr>
                        <th>Product</th>
                        <th>Unit</th>
                        <th>Stock Qty</th>
                        <th>Total Sold</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($outOfStock as $stock)
                        <tr>
                            <td>{{ $stock['product']->name ?? '' }}</td>
                            <td>{{ $stock['unit']->name ?? '' }}</td>
                            <td><span class="badge bg-danger">{{ $stock['qty'] }}</span></td>
                            <td><span class="badge bg-primary">{{ $stock['sold_qty'] ?? 0 }}</span></td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="4" class="text-center text-muted">No products available.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<script>
function showTable(id, btn) {
    document.querySelectorAll('.stock-table').forEach(el => el.classList.add('d-none'));
    document.getElementById(id).classList.remove('d-none');

    document.querySelectorAll('.btn-group .btn').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
}
</script>
@endsection
