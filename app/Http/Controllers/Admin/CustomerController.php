<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use App\Models\Customer;
use App\Models\Shop;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{

    public function __construct()
    {
        $this->middleware('permission:view customer')->only('index');
        $this->middleware('permission:create customer')->only(['create', 'store']);
        $this->middleware('permission:edit customer')->only(['edit', 'update']);
        $this->middleware('permission:delete customer')->only('destroy');
    }


    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $data = Customer::latest()->get();
        return view('admin.customer.index', compact('data'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $shop = Shop::where('status', 1)->get();
        return view('admin.customer.create', compact('shop'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'shop_id' => 'required|exists:shops,id', // এখন shop_id request থেকে আসবে
            'phone' => [
                'required',
                'regex:/^(01)[0-9]{9}$/',
                Rule::unique('customers', 'phone')->where(function ($query) use ($request) {
                    return $query->where('shop_id', $request->shop_id);
                }),
            ],
        ]);

        $data = $request->all();

        // Image upload
        $data['image'] = $request->hasFile('image')
            ? ImageHelper::uploadImage($request->file('image'))
            : null;

        // এখন আর auth()->guard('shop') থেকে নিচ্ছি না
        // $data['shop_id'] = auth()->guard('shop')->id();

        Customer::create($data);

        return redirect()->route('admin.customers.index')->with('success', 'Data Create Successfully');
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
        $data = Customer::findOrFail($id);
        $shop = Shop::where('status', 1)->get();
        return view('admin.customer.edit', compact('data', 'shop'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $data = Customer::findOrFail($id);

        // Validation
        $request->validate([
            'shop_id' => 'required|exists:shops,id', // এখন shop_id request থেকে আসবে
            'phone' => [
                'required',
                'regex:/^(01)[0-9]{9}$/',
                Rule::unique('customers', 'phone')
                    ->where(function ($query) use ($request) {
                        return $query->where('shop_id', $request->shop_id);
                    })
                    ->ignore($data->id),
            ],
        ]);

        // Image upload
        $image = $request->hasFile('image')
            ? ImageHelper::uploadImage($request->file('image'))
            : null;

        // Delete old image if new uploaded
        if ($image && $data->image) {
            Storage::disk('public')->delete($data->image);
        }

        $input = $request->all();

        if ($image) {
            $input['image'] = $image;
        }

        // এখন আর guard থেকে নিচ্ছি না
        // $input['shop_id'] = auth()->guard('shop')->id();

        $data->update($input);

        return redirect()->route('admin.customers.index')
            ->with('success', 'Data updated successfully');
    }


    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        $data = Customer::findOrFail($id);
        if ($data->image) {
            Storage::disk('public')->delete($data->image);
        }

        $data->delete();
        return redirect()->back()->with('success', 'Data Delete Successfully');
    }

    public function updateStatus(Request $request)
    {
        $item = Customer::findOrFail($request->id);
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
