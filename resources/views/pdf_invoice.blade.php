<!DOCTYPE html>
<html>
<head>
    <title>Invoice {{ $trx->invoice_no }}</title>
    <style>
        body { font-family: sans-serif; font-size: 12px; color: #333; }
        .header { text-align: center; margin-bottom: 20px; border-bottom: 2px solid #198754; padding-bottom: 10px; }
        .logo { width: 80px; height: auto; margin-bottom: 5px; }
        .shop-name { font-size: 20px; font-weight: bold; color: #198754; margin: 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        .table th { background: #f2f2f2; padding: 10px; border: 1px solid #ddd; text-align: left; }
        .table td { padding: 10px; border: 1px solid #ddd; }
        .total { font-weight: bold; font-size: 16px; color: #198754; text-align: right; margin-top: 15px; }
        .footer { margin-top: 30px; text-align: center; }
        .bank-info { border: 1px solid #ddd; padding: 10px; background: #f9f9f9; display: inline-block; }
    </style>
</head>
<body>

    <div class="header">
        @php
            // Mengambil gambar dan merubahnya ke format Base64
            $path = public_path('logo-bg-trans.png');
            $base64 = '';
            if(file_exists($path)){
                $type = pathinfo($path, PATHINFO_EXTENSION);
                $data = file_get_contents($path);
                $base64 = 'data:image/' . $type . ';base64,' . base64_encode($data);
            }
        @endphp

        @if($base64)
            <img src="{{ $base64 }}" class="logo">
        @endif

        <div class="shop-name">BaReng7 Vegie</div>
        <p>WA: 895-3338-08937 | Tanggal: {{ date('d-m-Y H:i:s', strtotime($trx->created_at)) }}</p>
    </div>

    <p><strong>No Invoice:</strong> {{ $trx->invoice_no }}<br>
    <strong>Customer:</strong> {{ strtoupper($trx->customer_name) }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>Item</th>
                <th>Qty</th>
                <th>Harga</th>
                <th>Subtotal</th>
            </tr>
        </thead>
        <tbody>
            @foreach($items as $item)
            <tr>
                <td>{{ $item->product_name }}</td>
                <td>{{ $item->qty }}</td>
                <td>Rp {{ number_format($item->price_sell, 0, ',', '.') }}</td>
                <td>Rp {{ number_format($item->qty * $item->price_sell, 0, ',', '.') }}</td>
            </tr>
            @endforeach
        </tbody>
    </table>

    <h3 class="total">Total: Rp {{ number_format($trx->total_sales, 0, ',', '.') }}</h3>

    <div class="footer">
        <div class="bank-info">
            <strong>Pembayaran dapat ditransfer ke rekening:</strong><br>
            BCA: 1560815600 a.n Merla Rosita
        </div>
        <p><em>Thank you for your order</em></p>
    </div>

</body>
</html>