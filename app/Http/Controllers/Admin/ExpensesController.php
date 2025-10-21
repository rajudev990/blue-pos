<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Expense;
use App\Models\Shop;
use Illuminate\Http\Request;

class ExpensesController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view expense')->only('index');
        $this->middleware('permission:create expense')->only(['create', 'store']);
        $this->middleware('permission:edit expense')->only(['edit', 'update']);
        $this->middleware('permission:delete expense')->only('destroy');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Expense::latest()->get();
        return view('admin.expense.index',compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shops = Shop::where('status',1)->get();
        return view('admin.expense.create',compact('shops'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $data = $request->all();
        Expense::create($data);
        return redirect()->route('admin.expenses.index')->with('success', 'Data Create Successfully');
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        $data = Expense::findOrFail($id);
        $shops = Shop::where('status',1)->get();
        return view('admin.expense.edit',compact('data','shops'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        $data = Expense::findOrFail($id);
        $shops = Shop::where('status',1)->get();
        return view('admin.expense.edit',compact('data','shops'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = Expense::findOrFail($id);
        $input = $request->all();
        $data->update($input);
        return redirect()->route('admin.expenses.index')->with('success', 'Data Updated Successfully');

    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Expense::findOrFail($id);
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
