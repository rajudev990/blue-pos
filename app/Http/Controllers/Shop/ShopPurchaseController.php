<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Unit;
use Illuminate\Http\Request;

class ShopPurchaseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Purchase::with('product', 'saleItems')->where('shop_id', auth()->guard('shop')->id())->latest()->get();
        return view('shop.purchase.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $unit = Unit::where('shop_id', auth()->guard('shop')->id())->where('status', 1)->get();
        $product = Product::where('shop_id', auth()->guard('shop')->id())->where('status', 1)->get();
        return view('shop.purchase.create', compact('unit', 'product'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        // Validation
        $request->validate([
            'product_id'       => 'required|exists:products,id',
            'unit_id'          => 'required|exists:units,id',
            'qty'              => 'required|numeric|min:1',
            'buy_price'        => 'required|numeric|min:0',
            'vat'              => 'nullable|numeric|min:0',
            'purchases_date'   => 'nullable|date',
        ]);

        $input = $request->all();

        // Shop ID automatically
        $input['shop_id'] = auth()->guard('shop')->id();

        // Calculate final_price and per_product_price
        $qty = floatval($input['qty']);
        $buyPrice = floatval($input['buy_price']);
        $vat = floatval($input['vat'] ?? 0);
        $finalPrice = $buyPrice + $vat;
        $perProductPrice = $qty > 0 ? ($finalPrice / $qty) : 0;

        $input['final_price'] = round($finalPrice, 2);
        $input['per_product_price'] = round($perProductPrice, 2);

        Purchase::create($input);

        return redirect()->route('shop.purchases.index')
            ->with('success', 'Data Create Successfully');
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
        $data = Purchase::where('shop_id', auth()->guard('shop')->id())->findOrFail($id);
        $unit = Unit::Where('status', 1)->get();
        $product = Product::Where('status', 1)->get();
        return view('shop.purchase.edit', compact('data', 'unit', 'product'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        // শুধু logged-in shop এর purchase accessable
        $data = Purchase::where('shop_id', auth()->guard('shop')->id())->findOrFail($id);

        // Validation
        $request->validate([
            'product_id'       => 'required|exists:products,id',
            'unit_id'          => 'required|exists:units,id',
            'qty'              => 'required|numeric|min:1',
            'buy_price'        => 'required|numeric|min:0',
            'vat'              => 'nullable|numeric|min:0',
            'final_price'      => 'required|numeric|min:0',
            'per_product_price' => 'required|numeric|min:0',
            'purchases_date'   => 'nullable|date',
        ]);

        $input = $request->all();

        // Shop ID always logged-in shop
        $input['shop_id'] = auth()->guard('shop')->id();

        // Optionally: calculate final_price and per_product_price automatically
        $qty = floatval($input['qty']);
        $buyPrice = floatval($input['buy_price']);
        $vat = floatval($input['vat'] ?? 0);
        $finalPrice = $buyPrice + $vat;
        $perProductPrice = $qty > 0 ? $finalPrice / $qty : 0;

        $input['final_price'] = round($finalPrice, 2);
        $input['per_product_price'] = round($perProductPrice, 2);

        $data->update($input);

        return redirect()->route('shop.purchases.index')
            ->with('success', 'Purchase updated successfully');
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
}
