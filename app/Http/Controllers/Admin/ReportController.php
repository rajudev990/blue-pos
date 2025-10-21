<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Payment;
use App\Models\Sale;
use App\Models\Shop;
use Illuminate\Http\Request;

class ReportController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view report')->only('index');
        $this->middleware('permission:create report')->only(['create', 'store']);
        $this->middleware('permission:edit report')->only(['edit', 'update']);
        $this->middleware('permission:delete report')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // সব শপের লিস্ট ফিল্টার ফর্মের জন্য
        $shops = \App\Models\Shop::where('status', 1)->get();

        $data = collect();
        $type = $request->input('type');
        $start = $request->input('start_date');
        $end   = $request->input('end_date');
        $shopId = $request->input('shop_id'); // form থেকে আসবে

        if ($request->filled(['start_date', 'end_date', 'type', 'shop_id'])) {
            if ($type === 'purchase') {
                $data = \App\Models\Purchase::with(['product', 'unit'])
                    ->where('shop_id', $shopId)
                    ->whereBetween('purchases_date', [$start, $end])
                    ->get();
            } elseif ($type === 'sale') {
                $data = \App\Models\Sale::with(['customer', 'items.product', 'items.unit', 'payments'])
                    ->where('shop_id', $shopId)
                    ->whereBetween('sale_date', [$start, $end])
                    ->get();
            } elseif ($type === 'expense') {
                $data = \App\Models\Expense::where('shop_id', $shopId)
                    ->whereBetween('date', [$start, $end])
                    ->get();
            }
        }

        return view('admin.reports.index', compact('data', 'type', 'start', 'end', 'shops', 'shopId'));
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

    public function dueList(Request $request)
    {
        // সব shop নিয়ে আসব filter form এর জন্য
        $shops = Shop::where('status', 1)->get();

        // filter থেকে shop_id নিলাম (nullable)
        $filterShopId = $request->get('shop_id');

        // base query
        $query = Sale::with(['payments', 'customer', 'shop']);

        // যদি shop select করা হয় তাহলে filter হবে
        if ($filterShopId) {
            $query->where('shop_id', $filterShopId);
        }

        $sales = $query->get()->map(function ($sale) {
            // total paid হিসাব
            $totalPaid = $sale->payments->sum('paid');

            // due হিসাব
            $due = ($sale->price ?? 0) - $totalPaid;

            $sale->total_paid = $totalPaid;
            $sale->due        = $due;

            return $sale;
        })
            // শুধু যেখানে due > 0
            ->filter(function ($sale) {
                return $sale->due > 0;
            });

        return view('admin.reports.due-list', compact('sales', 'shops', 'filterShopId'));
    }


    // Payment form
    public function payment($id)
    {
        $data = Sale::with('payments', 'customer')->findOrFail($id);
        return view('admin.reports.payment-form', compact('data'));
    }

    // Payment store
    public function paymentStore(Request $request)
    {
        $request->validate([
            'paid' => 'required|numeric|min:0',
        ]);

        $sale = Sale::findOrFail($request->sale_id);

        // Prevent overpayment
        $totalPaid = $sale->payments->sum('paid');
        $due = ($sale->price ?? 0) - $totalPaid;

        if ($request->paid > $due) {
            return back()->withErrors(['paid' => 'Paid amount cannot exceed the due amount!']);
        }

        Payment::create([
            'shop_id' => $sale->shop_id,
            'sale_id' => $sale->id,
            'customer_id' => $sale->customer_id,
            'paid' => $request->paid,
            'payment_date' => now()->format('Y-m-d'),
            'payment_by' => $request->payment_by,
        ]);

        return redirect()->route('admin.duelist')->with('success', 'Payment successfully added.');
    }
}
