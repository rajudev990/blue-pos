<?php

use Illuminate\Support\Facades\Auth;

if (!function_exists('shopCurrency')) {
    function shopCurrency()
    {
        $shop = Auth::guard('shop')->user();

        // currency যদি থাকে → brackets এ wrap করে দেখাবে, না হলে কিছু দেখাবে না
        return $shop && !empty($shop->currency) ? $shop->currency : '';
    }
}


if (!function_exists('adminCurrency')) {
    function adminCurrency()
    {
        $admin = Auth::guard('admin')->user();

        // currency যদি থাকে → brackets এ wrap করে দেখাবে, না হলে কিছু দেখাবে না
        return $admin && !empty($admin->currency) ? $admin->currency : '';
    }
}