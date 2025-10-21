<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Shop;
use App\Models\Unit;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view purchase')->only('index');
        $this->middleware('permission:create purchase')->only(['create', 'store']);
        $this->middleware('permission:edit purchase')->only(['edit', 'update']);
        $this->middleware('permission:delete purchase')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Purchase::latest()->get();
        return view('admin.purchase.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shop = Shop::Where('status', 1)->get();
        $unit = Unit::Where('status', 1)->get();
        $product = Product::Where('status', 1)->get();

        return view('admin.purchase.create', compact('shop', 'unit', 'product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id'          => 'required|exists:shops,id',
            'product_id'       => 'required|exists:products,id',
            'unit_id'          => 'required|exists:units,id',
            'qty'              => 'nullable|numeric|min:0',
            'buy_price'        => 'required|numeric|min:0',
            'vat'              => 'nullable|numeric|min:0',
            'purchases_date'     => 'nullable|date',
        ]);

        $input = $request->all();

        // Calculate final_price and per_product_price
        $qty = floatval($input['qty'] ?? 1);
        $buyPrice = floatval($input['buy_price']);
        $vat = floatval($input['vat'] ?? 0);

        $finalPrice = $buyPrice + $vat;
        $perProductPrice = $qty > 0 ? ($finalPrice / $qty) : $finalPrice;

        $input['final_price'] = round($finalPrice, 2);
        $input['per_product_price'] = round($perProductPrice, 2);

        Purchase::create($input);

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Purchase Created Successfully');
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
        $purchase = Purchase::findOrFail($id);
        $shops = Shop::all(); // যদি admin পেজ হয়
        $products = Product::where('status', 1)->get();
        $units = Unit::where('status', 1)->get();

        return view('admin.purchases.edit', compact('purchase', 'shops', 'products', 'units'));
    }

    /**
     * Update the specified resource in storage.
     */
    // Update Function
    public function update(Request $request, string $id)
    {
        $purchase = Purchase::findOrFail($id);

        $request->validate([
            'shop_id'      => 'required|exists:shops,id',
            'product_id'   => 'required|exists:products,id',
            'unit_id'      => 'required|exists:units,id',
            'qty'          => 'nullable|numeric|min:0',
            'buy_price'    => 'required|numeric|min:0',
            'vat'          => 'nullable|numeric|min:0',
            'purchases_date' => 'nullable|date',
        ]);

        $input = $request->all();

        $qty = floatval($input['qty'] ?? 1);
        $buyPrice = floatval($input['buy_price']);
        $vat = floatval($input['vat'] ?? 0);

        $finalPrice = $buyPrice + $vat;
        $perProductPrice = $qty > 0 ? ($finalPrice / $qty) : $finalPrice;

        $input['final_price'] = round($finalPrice, 2);
        $input['per_product_price'] = round($perProductPrice, 2);

        $purchase->update($input);

        return redirect()->route('admin.purchases.index')
            ->with('success', 'Purchase Updated Successfully');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Purchase::findOrFail($id);

        $data->delete();
        return redirect()->back()->with('success', 'Data Delete Successfully');
    }

    public function updateStatus(Request $request)
    {
        $item = Purchase::findOrFail($request->id);
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

    // PurchaseController
    public function getShopProductsUnits($shop_id)
    {
        $products = Product::where('shop_id', $shop_id)->where('status', 1)->get();
        $units = Unit::where('shop_id', $shop_id)->where('status', 1)->get();

        return response()->json([
            'products' => $products,
            'units' => $units
        ]);
    }
}
