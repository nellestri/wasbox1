<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Receipt - {{ $order->tracking_number }}</title>
    <style>
        /* Reset & Base */
        @page { size: 80mm 200mm; margin: 0; }
        body { font-family: 'Courier New'; width: 70mm; margin: 0 auto; padding: 5mm; font-size: 12px; }

        /* Layout */
        .t { text-align: center; }
        .d { border-top: 1px dashed #000; margin: 8px 0; }
        .sd { border-top: 2px solid #000; margin: 10px 0; }
        .fb { display: flex; justify-content: space-between; margin: 2px 0; }

        /* Elements */
        .bc { margin: 8px 0; font-size: 14px; font-weight: bold; letter-spacing: 2px; }
        .total { font-size: 16px; font-weight: bold; margin: 12px 0; }
        .status { display: inline-block; padding: 4px 12px; background: #000; color: #fff; font-weight: bold; }
        .note { font-size: 10px; background: #f9f9f9; padding: 5px; margin: 5px 0; }
        .small { font-size: 10px; }
        .bold { font-weight: bold; }

        /* Print */
        @media print { .no-print { display: none !important; } body { padding: 0; margin: 0; } }
        .print-btn { background: #3D3B6B; color: white; padding: 10px; border: none; border-radius: 6px; cursor: pointer; }
    </style>
</head>
<body onload="window.print()">

    <div class="no-print" style="background:#fef3c7;padding:15px;text-align:center;margin-bottom:20px;">
        <button onclick="window.print()" class="print-btn">🖨️ Print Receipt</button>
        <p class="small" style="color:#92400e;margin:10px 0 0;"><strong>Note:</strong> 80mm thermal paper</p>
    </div>

    <div>
        <div class="t">
            <h2 style="margin:3px 0;font-size:18px;">WASHBOX</h2>
            <h3 style="margin:3px 0;font-size:13px;">Laundry Services</h3>
            <p class="small"><strong>{{ $order->branch->name }}</strong><br>{{ Str::limit($order->branch->address,35) }}</p>
        </div>

        <div class="d"></div>
        <div class="t bc">* {{ $order->tracking_number }} *</div>
        <div class="d"></div>

        <div class="fb"><span>Date:</span><span>{{ $order->created_at->format('M d, h:i A') }}</span></div>
        <div class="fb"><span>Customer:</span><span class="bold">{{ $order->customer->name }}</span></div>
        @if($order->customer->phone)<div class="fb"><span>Phone:</span><span>{{ $order->customer->phone }}</span></div>@endif

        <div class="d"></div>
        <div class="fb bold"><span>DESCRIPTION</span><span>AMOUNT</span></div>
        <div class="fb"><span>{{ $order->service->name }}</span><span>₱{{ number_format($order->subtotal,2) }}</span></div>
        <p class="small t">{{ number_format($order->weight,2) }} kg × ₱{{ number_format($order->price_per_kg,2) }}/kg</p>

        @if($order->pickup_fee > 0 || $order->delivery_fee > 0)
            <div class="d"></div>
            @if($order->pickup_fee > 0)<div class="fb small"><span>Pickup Fee:</span><span>₱{{ number_format($order->pickup_fee,2) }}</span></div>@endif
            @if($order->delivery_fee > 0)<div class="fb small"><span>Delivery Fee:</span><span>₱{{ number_format($order->delivery_fee,2) }}</span></div>@endif
        @endif

        @if($order->discount_amount > 0)<div class="fb"><span>Discount:</span><span>-₱{{ number_format($order->discount_amount,2) }}</span></div>@endif

        <div class="sd"></div>
        <div class="fb total"><span>TOTAL:</span><span>₱{{ number_format($order->total_amount,2) }}</span></div>

        <div class="t"><div class="status">{{ strtoupper($order->status) }}</div></div>

        @if($order->pickupRequest)
            <div class="d"></div>
            <div class="note">
                <div class="bold">Pickup Service</div>
                <div>Type: {{ $order->pickupRequest->service_type_label }}</div>
                @if($order->pickupRequest->pickup_address)<div>Address: {{ Str::limit($order->pickupRequest->pickup_address,35) }}</div>@endif
            </div>
        @endif

        @if($order->pickup_date || $order->delivery_date)
            <div class="d"></div>
            <div class="small">
                @if($order->pickup_date)<div class="fb"><span>Pickup:</span><span>{{ \Carbon\Carbon::parse($order->pickup_date)->format('M d') }}</span></div>@endif
                @if($order->delivery_date)<div class="fb"><span>Delivery:</span><span>{{ \Carbon\Carbon::parse($order->delivery_date)->format('M d') }}</span></div>@endif
            </div>
        @endif

        @if($order->notes)
            <div class="d"></div>
            <div class="note"><strong>Notes:</strong><br>{{ $order->notes }}</div>
        @endif

        <div class="sd"></div>
        <div class="t">
            <p class="bold">PRESENT THIS TICKET</p>
            @if($order->staff)<p class="small">Served by: {{ $order->staff->name }}</p>@endif
            <p class="small">Receipt #: {{ $order->id }}</p>
            <p class="small">Printed: {{ now()->format('M d, h:i A') }}</p>
            <div class="d"></div>
            <p class="bold">THANK YOU!</p>
            <p class="small">{{ $order->branch->phone ?? 'Contact branch' }}</p>
        </div>
    </div>

</body>
</html>
