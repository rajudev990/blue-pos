<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use Illuminate\Http\Request;

class ShopExpenseController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Expense::where('shop_id', auth()->guard('shop')->id())->latest()->get();
        return view('shop.expense.index',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('shop.expense.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        $data['shop_id'] = auth()->guard('shop')->id();
        Expense::create($data);
        return redirect()->route('shop.expenses.index')->with('success', 'Data Create Successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Expense::where('shop_id', auth()->guard('shop')->id())->findOrFail($id);
        return view('shop.expense.edit',compact('data'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = Expense::where('shop_id', auth()->guard('shop')->id())->findOrFail($id);
        return view('shop.expense.edit',compact('data'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = Expense::findOrFail($id);
        $input = $request->all();
        $input['shop_id'] = auth()->guard('shop')->id();
        $data->update($input);
        return redirect()->route('shop.expenses.index')->with('success', 'Data Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Expense::where('shop_id', auth()->guard('shop')->id())->findOrFail($id);
        $data->delete();
        return redirect()->back()->with('success', 'Data Delete Successfully');
    }

    public function updateStatus(Request $request)
    {
        $item = Expense::findOrFail($request->id);
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
