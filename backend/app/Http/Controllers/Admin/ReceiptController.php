<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Order;

class ReceiptController extends Controller
{
    public function show($orderId)
    {
        $order = Order::findOrFail($orderId);
        return view('admin.receipts.show', compact('order'));
    }
}

