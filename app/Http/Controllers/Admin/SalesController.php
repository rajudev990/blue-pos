<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Payment;
use App\Models\Product;
use App\Models\Purchase;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Shop;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\DB;

class SalesController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:view sales')->only('index');
        $this->middleware('permission:create sales')->only(['create', 'store']);
        $this->middleware('permission:edit sales')->only(['edit', 'update']);
        $this->middleware('permission:delete sales')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Sale::latest()->get();
        return view('admin.sale.index', compact('data'));
    }


    /**
     * Show the form for creating a new resource.
     */
    // public function create()
    // {
    //     $shop = Shop::Where('status', 1)->get();
    //     $customer = Customer::Where('status', 1)->get();
    //     return view('admin.sale.create', compact('shop', 'customer'));
    // }

    // /**
    //  * Store a newly created resource in storage.
    //  */
    // public function store(Request $request)
    // {
    //     $data = $request->all();
    //     Sale::create($data);
    //     return redirect()->route('admin.sales.index')->with('success', 'Data Create Successfully');
    // }


    // Show create form (initial)
    public function create(Request $request)
    {
        // load all active shops
        $shops = Shop::where('status', 1)->get();

        // if admin opened with a shop_id (optional), load shop-specific data
        $selectedShopId = $request->shop_id ?? null;

        $customers = collect();
        $products = collect();
        $units = collect();
        $productUnitStock = [];
        $productUnits = [];

        if ($selectedShopId) {
            $customers = Customer::where('shop_id', $selectedShopId)->get();
            // build productUnitStock & productUnits (same logic as your earlier code)
            $purchases = Purchase::where('shop_id', $selectedShopId)->where('qty', '>', 0)->get();

            foreach ($purchases as $p) {
                $key = $p->product_id . '_' . $p->unit_id;
                if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
                $productUnitStock[$key] += (int)$p->qty;
            }

            $saleItems = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $selectedShopId))->get();
            foreach ($saleItems as $si) {
                $key = $si->product_id . '_' . $si->unit_id;
                if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
                $productUnitStock[$key] -= (int)$si->qty;
            }

            foreach ($productUnitStock as $key => $stock) {
                if ($stock > 0) {
                    [$pid, $uid] = explode('_', $key);
                    if (!isset($productUnits[$pid])) $productUnits[$pid] = [];
                    if (!in_array($uid, $productUnits[$pid])) $productUnits[$pid][] = $uid;
                }
            }

            $productIdsWithStock = array_keys($productUnits);
            if (count($productIdsWithStock)) {
                $products = Product::where('shop_id', $selectedShopId)->whereIn('id', $productIdsWithStock)->get();
            } else {
                $products = collect();
            }

            $unitIdsWithStock = collect($productUnits)->flatten()->unique()->toArray();
            $units = Unit::where('shop_id', $selectedShopId)->whereIn('id', $unitIdsWithStock)->get();
        }

        return view('admin.sale.create', compact(
            'shops',
            'customers',
            'products',
            'units',
            'productUnitStock',
            'productUnits',
            'selectedShopId'
        ));
    }

    // Ajax: get customers for a shop
    public function getCustomers($shop_id)
    {
        $customers = Customer::where('shop_id', $shop_id)->get(['id', 'name', 'phone']);
        return response()->json($customers);
    }

    // Ajax: get shop data - products, units, productUnitStock mapping and productUnits mapping
    public function getShopData($shop_id)
    {
        $purchases = Purchase::where('shop_id', $shop_id)->where('qty', '>', 0)->get();

        $productUnitStock = [];
        foreach ($purchases as $p) {
            $key = $p->product_id . '_' . $p->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] += (int)$p->qty;
        }

        $saleItems = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shop_id))->get();
        foreach ($saleItems as $si) {
            $key = $si->product_id . '_' . $si->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] -= (int)$si->qty;
        }

        $productUnits = [];
        foreach ($productUnitStock as $key => $stock) {
            if ($stock > 0) {
                [$pid, $uid] = explode('_', $key);
                if (!isset($productUnits[$pid])) $productUnits[$pid] = [];
                if (!in_array($uid, $productUnits[$pid])) $productUnits[$pid][] = $uid;
            }
        }

        $productIdsWithStock = array_keys($productUnits);
        $products = Product::where('shop_id', $shop_id)->whereIn('id', $productIdsWithStock)->get(['id', 'name']);
        $unitIdsWithStock = collect($productUnits)->flatten()->unique()->toArray();
        $units = Unit::where('shop_id', $shop_id)->whereIn('id', $unitIdsWithStock)->get(['id', 'name']);

        return response()->json([
            'products' => $products,
            'units' => $units,
            'productUnits' => $productUnits,
            'productUnitStock' => $productUnitStock,
        ]);
    }

    // Ajax: voucher check (shop-wise)
    public function checkVoucher(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'voucher_no' => 'required|string'
        ]);

        $exists = Sale::where('shop_id', $request->shop_id)
            ->where('voucher_no', $request->voucher_no)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    // Ajax: check stock for product+unit+shop (fallback server check)
    public function checkStock(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|integer|exists:shops,id',
            'product_id' => 'required|integer|exists:products,id',
            'unit_id' => 'required|integer|exists:units,id',
        ]);

        $shopId = $request->shop_id;
        $productId = $request->product_id;
        $unitId = $request->unit_id;

        $purchased = Purchase::where('shop_id', $shopId)
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->sum('qty');

        $sold = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId))
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->sum('qty');

        $available = max(0, (int)$purchased - (int)$sold);

        return response()->json(['available' => $available]);
    }

    // Store sale with FIFO allocation
    public function store(Request $request)
    {
        $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'customer_id' => 'required|exists:customers,id',
            'voucher_no'  => [
                'required',
                Rule::unique('sales', 'voucher_no')->where(fn($q) => $q->where('shop_id', $request->shop_id)),
            ],
            'sale_date'   => 'required|date',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id'    => 'required|exists:units,id',
            'items.*.qty'        => 'required|numeric|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'paid'        => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'vat'         => 'nullable|numeric|min:0',
            'price'       => 'required|numeric|min:0',
        ]);

        $shopId = $request->shop_id;

        DB::beginTransaction();

        try {
            $sale = Sale::create([
                'shop_id'     => $shopId,
                'customer_id' => $request->customer_id,
                'voucher_no'  => $request->voucher_no,
                'sale_date'   => $request->sale_date,
                'vat'         => $request->vat ?? 0,
                'price'       => $request->price,
                'status'      => 1,
            ]);

            // For each item, allocate from purchases (same unit) FIFO
            foreach ($request->items as $index => $item) {
                $productId = $item['product_id'];
                $unitId = $item['unit_id'];
                $need = (int)$item['qty'];
                $price = $item['price'];

                if ($need <= 0) continue;

                // get purchases for this product+unit (shop) ordered oldest first
                $purchases = Purchase::where('shop_id', $shopId)
                    ->where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->orderBy('id', 'asc')
                    ->get();

                foreach ($purchases as $purchase) {
                    $soldFromThisPurchase = SaleItem::where('purchase_id', $purchase->id)->sum('qty');
                    $availableFromThisPurchase = max(0, (int)$purchase->qty - (int)$soldFromThisPurchase);

                    if ($availableFromThisPurchase <= 0) continue;

                    $assign = min($need, $availableFromThisPurchase);

                    SaleItem::create([
                        'sale_id'     => $sale->id,
                        'purchase_id' => $purchase->id,
                        'product_id'  => $productId,
                        'unit_id'     => $unitId,
                        'qty'         => $assign,
                        'price'       => $price,
                    ]);

                    $need -= $assign;
                    if ($need <= 0) break;
                }

                if ($need > 0) {
                    DB::rollBack();
                    return back()->withErrors(['items' => "Not enough stock for product ID {$productId} and unit ID {$unitId}. Remaining needed: {$need}"])->withInput();
                }
            }

            if (!empty($request->paid) && $request->paid > 0) {
                Payment::create([
                    'shop_id' => $shopId,
                    'sale_id' => $sale->id,
                    'customer_id' => $request->customer_id,
                    'paid' => $request->paid,
                    'payment_date' => $request->sale_date,
                    'payment_by' => $request->payment_by ?? 'Cash',
                ]);
            }

            DB::commit();
            return redirect()->route('admin.sales.index')->with('success', 'Sale Created Successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
    }


    // Show edit form
    public function edit($id)
    {
        $sale = Sale::findOrFail($id);

        $shops = Shop::where('status', 1)->get();
        $selectedShopId = $sale->shop_id;

        $customers = Customer::where('shop_id', $selectedShopId)->get();

        $purchases = Purchase::where('shop_id', $selectedShopId)->where('qty', '>', 0)->get();
        $productUnitStock = [];

        foreach ($purchases as $p) {
            $key = $p->product_id . '_' . $p->unit_id;
            $productUnitStock[$key] = ($productUnitStock[$key] ?? 0) + $p->qty;
        }

        // Subtract other sales except this one
        $saleItems = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $selectedShopId)->where('id', '!=', $sale->id))->get();
        foreach ($saleItems as $si) {
            $key = $si->product_id . '_' . $si->unit_id;
            $productUnitStock[$key] = ($productUnitStock[$key] ?? 0) - $si->qty;
        }

        $productUnits = [];
        foreach ($productUnitStock as $key => $stock) {
            if ($stock > 0) {
                [$pid, $uid] = explode('_', $key);
                $productUnits[$pid][] = $uid;
            }
        }

        $productIdsWithStock = array_keys($productUnits);
        $products = Product::where('shop_id', $selectedShopId)->whereIn('id', $productIdsWithStock)->get();
        $unitIdsWithStock = collect($productUnits)->flatten()->unique()->toArray();
        $units = Unit::where('shop_id', $selectedShopId)->whereIn('id', $unitIdsWithStock)->get();

        return view('admin.sale.edit', compact(
            'shops',
            'customers',
            'products',
            'units',
            'productUnitStock',
            'productUnits',
            'selectedShopId',
            'sale'
        ));
    }

    // Update sale
    public function update(Request $request, $id)
    {
        $sale = Sale::findOrFail($id);

        $request->validate([
            'shop_id'     => 'required|exists:shops,id',
            'customer_id' => 'required|exists:customers,id',
            'voucher_no'  => [
                'required',
                Rule::unique('sales', 'voucher_no')->where(fn($q) => $q->where('shop_id', $request->shop_id))->ignore($sale->id),
            ],
            'sale_date'   => 'required|date',
            'items'       => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.unit_id'    => 'required|exists:units,id',
            'items.*.qty'        => 'required|numeric|min:1',
            'items.*.price'      => 'required|numeric|min:0',
            'paid'        => ['nullable', 'numeric', 'min:0', 'lte:price'],
            'vat'         => 'nullable|numeric|min:0',
            'price'       => 'required|numeric|min:0',
        ]);

        $shopId = $request->shop_id;

        DB::beginTransaction();

        try {
            // Update sale main record
            $sale->update([
                'shop_id'     => $shopId,
                'customer_id' => $request->customer_id,
                'voucher_no'  => $request->voucher_no,
                'sale_date'   => $request->sale_date,
                'vat'         => $request->vat ?? 0,
                'price'       => $request->price,
            ]);

            // Delete existing sale items
            $sale->items()->delete();

            // Re-allocate items with FIFO
            foreach ($request->items as $index => $item) {
                $productId = $item['product_id'];
                $unitId = $item['unit_id'];
                $need = (int)$item['qty'];
                $price = $item['price'];

                if ($need <= 0) continue;

                $purchases = Purchase::where('shop_id', $shopId)
                    ->where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->orderBy('id', 'asc')
                    ->get();

                $availableTotal = 0;
                foreach ($purchases as $purchase) {
                    $soldFromThisPurchase = SaleItem::where('purchase_id', $purchase->id)
                        ->where('sale_id', '!=', $sale->id)
                        ->sum('qty');
                    $availableFromThisPurchase = max(0, (int)$purchase->qty - (int)$soldFromThisPurchase);
                    $availableTotal += $availableFromThisPurchase;
                }

                if ($availableTotal < $need) {
                    $productName = optional(\App\Models\Product::find($productId))->name ?? "Unknown Product";
                    $unitName = optional(\App\Models\Unit::find($unitId))->name ?? "Unknown Unit";
                    DB::rollBack();
                    return back()->withErrors([
                        'items' => "Not enough stock for {$productName} ({$unitName}). Requested: {$need}, Available: {$availableTotal}"
                    ])->withInput();
                }

                // normal FIFO allocation
                foreach ($purchases as $purchase) {
                    $soldFromThisPurchase = SaleItem::where('purchase_id', $purchase->id)
                        ->where('sale_id', '!=', $sale->id)
                        ->sum('qty');
                    $availableFromThisPurchase = max(0, (int)$purchase->qty - (int)$soldFromThisPurchase);

                    if ($availableFromThisPurchase <= 0) continue;

                    $assign = min($need, $availableFromThisPurchase);

                    SaleItem::create([
                        'sale_id'     => $sale->id,
                        'purchase_id' => $purchase->id,
                        'product_id'  => $productId,
                        'unit_id'     => $unitId,
                        'qty'         => $assign,
                        'price'       => $price,
                    ]);

                    $need -= $assign;
                    if ($need <= 0) break;
                }
            }


            // Update payment
            $payment = Payment::where('sale_id', $sale->id)->first();
            if ($payment) {
                $payment->update([
                    'shop_id' => $shopId,
                    'customer_id' => $request->customer_id,
                    'paid' => $request->paid ?? 0,
                    'payment_date' => $request->sale_date,
                    'payment_by' => $request->payment_by ?? 'Cash',
                ]);
            } elseif (!empty($request->paid) && $request->paid > 0) {
                Payment::create([
                    'shop_id' => $shopId,
                    'sale_id' => $sale->id,
                    'customer_id' => $request->customer_id,
                    'paid' => $request->paid,
                    'payment_date' => $request->sale_date,
                    'payment_by' => $request->payment_by ?? 'Cash',
                ]);
            }

            DB::commit();
            return redirect()->route('admin.sales.index')->with('success', 'Sale Updated Successfully!');
        } catch (\Throwable $e) {
            DB::rollBack();
            return back()->withErrors(['error' => $e->getMessage()])->withInput();
        }
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
    // public function edit(string $id)
    // {
    //     $data = Sale::findOrFail($id);
    //     $shop = Shop::Where('status', 1)->get();
    //     $customer = Customer::Where('status', 1)->get();
    //     return view('admin.sale.edit', compact('data', 'shop', 'customer'));
    // }

    // /**
    //  * Update the specified resource in storage.
    //  */
    // public function update(Request $request, string $id)
    // {
    //     $data = Sale::findOrFail($id);
    //     $input = $request->all();

    //     $data->update($input);
    //     return redirect()->route('admin.sales.index')->with('success', 'Data Update Successfully');
    // }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Sale::findOrFail($id);

        // related data delete
        $data->items()->delete();
        $data->payments()->delete();


        $data->delete();

        
        return redirect()->back()->with('success', 'Data Delete Successfully');
    }

    public function updateStatus(Request $request)
    {
        $item = Sale::findOrFail($request->id);
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
