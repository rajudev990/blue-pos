<?php

namespace App\Http\Controllers\Shop;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Payment;
use App\Models\Purchase;
use App\Models\Sale;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ShopReportController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $shopId = auth('shop')->id();

        $data = collect();
        $type = $request->input('type');
        $start = $request->input('start_date');
        $end   = $request->input('end_date');

        if ($request->filled(['start_date', 'end_date', 'type'])) {
            if ($type === 'purchase') {
                $data = Purchase::with(['product', 'unit'])
                    ->where('shop_id', $shopId)
                    ->whereBetween('purchases_date', [$start, $end])
                    ->get();
            } elseif ($type === 'sale') {
                $data = Sale::with(['customer', 'items.product', 'items.unit', 'payments'])
                    ->where('shop_id', $shopId)
                    ->whereBetween('sale_date', [$start, $end])
                    ->get();
            } elseif ($type === 'expense') {
                $data = Expense::where('shop_id', $shopId)
                    ->whereBetween('date', [$start, $end])
                    ->get();
            }
        }

        return view('shop.reports.index', compact('data', 'type', 'start', 'end'));
    }


    public function dueList(Request $request)
    {
        $shopId = Auth::guard('shop')->id(); // লগইন করা shop এর id

        // sales লিস্ট নাও শুধুমাত্র এই shop এর
        $sales = Sale::with(['payments'])
            ->where('shop_id', $shopId)
            ->get()
            ->map(function ($sale) {
                // total paid হিসাব
                $totalPaid = $sale->payments->sum('paid');

                // due = price - totalPaid
                $due = ($sale->price ?? 0) - $totalPaid;

                $sale->total_paid = $totalPaid;
                $sale->due = $due;

                return $sale;
            })
            // শুধুমাত্র যেখানে due > 0
            ->filter(function ($sale) {
                return $sale->due > 0;
            });

        return view('shop.reports.due-list', compact('sales'));
    }

    // Payment form
    public function payment($id)
    {
        $data = Sale::with('payments','customer')->findOrFail($id);
        return view('shop.reports.payment-form',compact('data'));
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

        if($request->paid > $due){
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

        return redirect()->route('shop.duelist')->with('success', 'Payment successfully added.');
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
