<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view stock')->only('index');
        $this->middleware('permission:create stock')->only(['create', 'store']);
        $this->middleware('permission:edit stock')->only(['edit', 'update']);
        $this->middleware('permission:delete stock')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // ফর্ম থেকে আসা shop_id ধরলাম
        $shopId = $request->get('type');

        if (!$shopId) {
            // যদি shop select না করে, ফাঁকা return করবে
            return view('admin.stock.index', [
                'inStock'    => collect([]),
                'lowStock'   => collect([]),
                'outOfStock' => collect([]),
            ]);
        }

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
            $productId    = $purchase->product_id;
            $unitId       = $purchase->unit_id;
            $purchasedQty = $purchase->total_purchased;

            $soldQty = optional(
                $sales->firstWhere(fn($s) => $s->product_id == $productId && $s->unit_id == $unitId)
            )->total_sold ?? 0;

            $qty = $purchasedQty - $soldQty;

            $stocks[] = [
                'product'   => Product::find($productId),
                'unit'      => Unit::find($unitId),
                'qty'       => $qty,
                'sold_qty'  => $soldQty,
            ];
        }

        // Filter by stock levels
        $inStock    = collect($stocks)->filter(fn($s) => $s['qty'] > 10);
        $lowStock   = collect($stocks)->filter(fn($s) => $s['qty'] <= 10 && $s['qty'] > 0);
        $outOfStock = collect($stocks)->filter(fn($s) => $s['qty'] == 0);

        return view('admin.stock.index', compact('inStock', 'lowStock', 'outOfStock'));
    }


    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {

        $sale = Sale::Where('status', 1)->get();
        $unit = Unit::Where('status', 1)->get();
        $product = Product::Where('status', 1)->get();
        return view('admin.saleitem.create', compact('sale', 'unit', 'product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        SaleItem::create($data);
        return redirect()->route('admin.stocks.index')->with('success', 'Data Create Successfully');
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
        $data = SaleItem::findOrFail($id);
        $sale = Sale::Where('status', 1)->get();
        $unit = Unit::Where('status', 1)->get();
        $product = Product::Where('status', 1)->get();
        return view('admin.saleitem.edit', compact('data', 'sale', 'unit', 'product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = SaleItem::findOrFail($id);
        $input = $request->all();

        $data->update($input);
        return redirect()->route('admin.stocks.index')->with('success', 'Data Update Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = SaleItem::findOrFail($id);

        $data->delete();
        return redirect()->back()->with('success', 'Data Delete Successfully');
    }

    public function updateStatus(Request $request)
    {
        $item = SaleItem::findOrFail($request->id);
        $item->status = $request->status;
        $item->save();

        // Check if the request is an AJAX request
        if ($request->ajax()) {
            return response()->json([
                'status' => $item->status,
                'message' => $item->status == 1
                    ? 'Status has been activated successfully.'
                    : 'Status has been deactivated successfully.'
            ]);
        }

        // In case it's not an AJAX request, redirect with a success message
        return back()->with('success', 'Status has been updated successfully.');
    }
}
