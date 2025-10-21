<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\ImageHelper;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

class AdminProfileController extends Controller
{

    public function settings()
    {
        return view('admin.auth.settings');
    }
    public function changePassword()
    {
        return view('admin.auth.change-password');
    }
    public function updateSettings(Request $request)
    {
        $admin = auth('admin')->user();

        $request->validate([
            'name'   => 'required|string|max:255',
            'email'  => 'required|email|unique:shops,email,' . $admin->id,
            'phone'  => 'nullable|string|max:20',
            'image'  => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);


        // Prepare update data
        $data = [
            'name'    => $request->name,
            'email'   => $request->email,
            'phone'   => $request->phone,
        ];

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($admin->image && Storage::disk('public')->exists($admin->image)) {
                Storage::disk('public')->delete($admin->image);
            }

            // Upload new image
            $data['image'] = ImageHelper::uploadImage($request->file('image'));
        }

        // Update admin profile
        $admin->update($data);

        return back()->with('success', 'Profile updated successfully.');
    }

    public function updatePassword(Request $request)
    {
        $admin = auth('admin')->user();

        $request->validate([
            'current_password' => 'required',
            'new_password' => 'required|min:6|confirmed',
        ]);

        if (!\Hash::check($request->current_password, $admin->password)) {
            return back()->withErrors(['current_password' => 'Current password does not match.']);
        }

        $admin->update([
            'password' => \Hash::make($request->new_password),
        ]);

        return back()->with('success', 'Password changed successfully.');
    }

    public function updateCurrency(Request $request)
    {
        $admin = auth('admin')->user();

        $request->validate([
            'currency' => 'required|string|in:৳,$,د.إ,ر.س'
        ]);

        $admin->currency = $request->currency;
        $admin->save();

       return back()->with('success', 'Currency updated successfully.');

    }
}
