<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\SaleItem;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ShopStockController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shopId = auth('shop')->id();

        // Purchases
        $purchases = Purchase::where('shop_id', $shopId)
            ->select('product_id', 'unit_id', DB::raw('SUM(qty) as total_purchased'))
            ->groupBy('product_id', 'unit_id')
            ->get();

        // Sales
        $sales = SaleItem::whereHas('sale', function ($q) use ($shopId) {
            $q->where('shop_id', $shopId);
        })
            ->select('product_id', 'unit_id', DB::raw('SUM(qty) as total_sold'))
            ->groupBy('product_id', 'unit_id')
            ->get();

        // Merge Stock Calculation
        $stocks = [];
        foreach ($purchases as $purchase) {
            $productId = $purchase->product_id;
            $unitId    = $purchase->unit_id;
            $purchasedQty = $purchase->total_purchased;

            $soldQty = optional(
                $sales->firstWhere(fn($s) => $s->product_id == $productId && $s->unit_id == $unitId)
            )->total_sold ?? 0;

            $qty = $purchasedQty - $soldQty;

            $stocks[] = [
                'product' => Product::find($productId),
                'unit'    => Unit::find($unitId),
                'qty'     => $qty,
                'sold_qty'  => $soldQty, // extra add
            ];
        }

        // Filter by stock levels
        $inStock    = collect($stocks)->filter(fn($s) => $s['qty'] > 10);
        $lowStock   = collect($stocks)->filter(fn($s) => $s['qty'] <= 10 && $s['qty'] > 0);
        $outOfStock = collect($stocks)->filter(fn($s) => $s['qty'] == 0);

        return view('shop.stock.index', compact('inStock', 'lowStock', 'outOfStock'));
    }



    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        //
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
