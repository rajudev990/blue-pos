<?php

namespace App\Http\Controllers\Shop;

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
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;


class ShopSalesController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $shopId = auth()->guard('shop')->id();
        $sales = Sale::where('shop_id', $shopId)->latest()->get();
        return view('shop.sales.index', compact('sales'));
    }


    public function create()
    {
        $shopId = auth()->guard('shop')->id();

        $shop = Shop::findOrFail($shopId);
        $customers = Customer::where('shop_id', $shopId)->get();

        // get all purchases for this shop
        $purchases = Purchase::where('shop_id', $shopId)
            ->where('qty', '>', 0)
            ->get();

        // Build mapping: product_id_unit_id => availableQty
        $productUnitStock = [];

        foreach ($purchases as $p) {
            $key = $p->product_id . '_' . $p->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] += (int)$p->qty;
        }

        // subtract sold qty
        $saleItems = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId))->get();
        foreach ($saleItems as $si) {
            $key = $si->product_id . '_' . $si->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] -= (int)$si->qty;
        }

        // Build product -> units mapping (only those with stock > 0)
        $productUnits = [];
        foreach ($productUnitStock as $key => $stock) {
            if ($stock > 0) {
                [$pid, $uid] = explode('_', $key);
                if (!isset($productUnits[$pid])) $productUnits[$pid] = [];
                if (!in_array($uid, $productUnits[$pid])) $productUnits[$pid][] = $uid;
            }
        }

        // Get only those products that actually have stock
        $productIdsWithStock = array_keys($productUnits);
        $products = Product::where('shop_id', $shopId)
            ->whereIn('id', $productIdsWithStock)
            ->get();

        // Get only those units that actually have stock
        $unitIdsWithStock = collect($productUnits)->flatten()->unique()->toArray();
        $units = Unit::where('shop_id', $shopId)
            ->whereIn('id', $unitIdsWithStock)
            ->get();

        return view('shop.sales.create', compact('shop', 'customers', 'products', 'units', 'productUnitStock', 'productUnits'));
    }


    public function checkVoucher(Request $request)
    {
        $exists = Sale::where('shop_id', $request->shop_id)
            ->where('voucher_no', $request->voucher_no)
            ->exists();

        return response()->json(['exists' => $exists]);
    }

    // AJAX: return available stock for product+unit for this shop
    public function checkStock(Request $request)
    {
        $request->validate([
            'product_id' => 'required|integer',
            'unit_id' => 'required|integer',
        ]);

        $shopId = auth()->guard('shop')->id();
        $productId = $request->product_id;
        $unitId = $request->unit_id;

        // total purchased qty for product+unit
        $purchased = Purchase::where('shop_id', $shopId)
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->sum('qty');

        // total sold qty for product+unit (via sale_items joined to sales for this shop)
        $sold = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId))
            ->where('product_id', $productId)
            ->where('unit_id', $unitId)
            ->sum('qty');

        $available = (int)$purchased - (int)$sold;
        if ($available < 0) $available = 0;

        return response()->json(['available' => $available]);
    }

    public function store(Request $request)
    {
        $shopId = auth()->guard('shop')->id();

        $request->validate([
            'customer_id' => 'required|exists:customers,id',
            'voucher_no'  => ['required', Rule::unique('sales', 'voucher_no')->where(fn($q) => $q->where('shop_id', $shopId))],
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
                    // sold qty from this purchase (sum of sale_items with this purchase_id)
                    $soldFromThisPurchase = SaleItem::where('purchase_id', $purchase->id)->sum('qty');
                    $availableFromThisPurchase = max(0, (int)$purchase->qty - (int)$soldFromThisPurchase);

                    if ($availableFromThisPurchase <= 0) continue;

                    $assign = min($need, $availableFromThisPurchase);

                    // create sale item referencing purchase_id
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
                    // not enough stock: rollback and return with error + old input
                    DB::rollBack();
                    return back()
                        ->withErrors(['items' => "Not enough stock for product ID {$productId} and unit ID {$unitId}. Remaining needed: {$need}"])
                        ->withInput();
                }
            }

            // Payment record if paid provided
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
            return redirect()->route('shop.sales.index')->with('success', 'Sale Created Successfully!');
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
        $sale = Sale::with(['items', 'payments'])->findOrFail($id);
        $shop = auth()->guard('shop')->user();
        $customers = Customer::where('shop_id', $shop->id)->get();
        $products = Product::where('shop_id', $shop->id)->get();
        $units = Unit::where('shop_id', $shop->id)->get();

        return view('shop.sales.view', compact('sale', 'shop', 'customers', 'products', 'units'));
    }

    public function edit($id)
    {
        $shopId = auth()->guard('shop')->id();
        $shop = Shop::findOrFail($shopId);

        $sale = Sale::with('items')->where('shop_id', $shopId)->findOrFail($id);
        $customers = Customer::where('shop_id', $shopId)->get();

        // get all purchases for this shop
        $purchases = Purchase::where('shop_id', $shopId)
            ->where('qty', '>', 0)
            ->get();

        // Build mapping: product_id_unit_id => availableQty
        $productUnitStock = [];
        foreach ($purchases as $p) {
            $key = $p->product_id . '_' . $p->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] += (int)$p->qty;
        }

        // subtract sold qty excluding current sale
        $saleItems = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId)->where('id', '!=', $id))->get();
        foreach ($saleItems as $si) {
            $key = $si->product_id . '_' . $si->unit_id;
            if (!isset($productUnitStock[$key])) $productUnitStock[$key] = 0;
            $productUnitStock[$key] -= (int)$si->qty;
        }

        // Build product -> units mapping (only those with stock > 0)
        $productUnits = [];
        foreach ($productUnitStock as $key => $stock) {
            if ($stock > 0) {
                [$pid, $uid] = explode('_', $key);
                if (!isset($productUnits[$pid])) $productUnits[$pid] = [];
                if (!in_array($uid, $productUnits[$pid])) $productUnits[$pid][] = $uid;
            }
        }

        $productIdsWithStock = array_keys($productUnits);
        $products = Product::where('shop_id', $shopId)->whereIn('id', $productIdsWithStock)->get();

        $unitIdsWithStock = collect($productUnits)->flatten()->unique()->toArray();
        $units = Unit::where('shop_id', $shopId)->whereIn('id', $unitIdsWithStock)->get();

        return view('shop.sales.edit', compact('shop', 'customers', 'sale', 'products', 'units', 'productUnitStock', 'productUnits'));
    }


    public function update(Request $request, $id)
{
    $shopId = auth()->guard('shop')->id();
    $sale = Sale::where('shop_id', $shopId)->findOrFail($id);

    $request->validate([
        'customer_id' => 'required|exists:customers,id',
        'voucher_no'  => ['required', Rule::unique('sales', 'voucher_no')->ignore($sale->id)->where(fn($q) => $q->where('shop_id', $shopId))],
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

    DB::beginTransaction();
    try {
        // Update main sale info
        $sale->update([
            'customer_id' => $request->customer_id,
            'voucher_no'  => $request->voucher_no,
            'sale_date'   => $request->sale_date,
            'vat'         => $request->vat ?? 0,
            'price'       => $request->price,
            'status'      => 1,
        ]);

        // Prepare old sale items mapping for stock rollback
        $oldItems = $sale->items()->get()->map(function($item){
            return [
                'product_id' => $item->product_id,
                'unit_id'    => $item->unit_id,
                'qty'        => $item->qty
            ];
        });

        // Remove old sale items & payments
        $sale->items()->delete();
        Payment::where('sale_id', $sale->id)->delete();

        // Allocate new items (FIFO)
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
                // get product & unit names
                $productName = Product::find($productId)?->name ?? "Product ID {$productId}";
                $unitName = Unit::find($unitId)?->name ?? "Unit ID {$unitId}";

                // total purchased qty
                $totalPurchased = Purchase::where('shop_id', $shopId)
                    ->where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->sum('qty');

                // total sold excluding current sale
                $totalSold = SaleItem::whereHas('sale', fn($q) => $q->where('shop_id', $shopId))
                    ->where('product_id', $productId)
                    ->where('unit_id', $unitId)
                    ->sum('qty');

                // Add old sale qty back to available
                $oldQty = $oldItems->firstWhere(fn($i)=>$i['product_id']==$productId && $i['unit_id']==$unitId)['qty'] ?? 0;
                $available = max(0, $totalPurchased - $totalSold + $oldQty);

                DB::rollBack();
                return back()
                    ->withErrors(['items' => "Not enough stock for {$productName} ({$unitName}). Available: {$available}. Remaining needed: {$need}"])
                    ->withInput();
            }
        }

        // Payment record if paid provided
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
        return redirect()->route('shop.sales.index')->with('success', 'Sale Updated Successfully!');
    } catch (\Throwable $e) {
        DB::rollBack();
        return back()->withErrors(['error' => $e->getMessage()])->withInput();
    }
}



    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        //
    }
}
