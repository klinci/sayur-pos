<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Modal - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-10">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <a href="{{ route('history') }}" class="btn btn-link text-decoration-none text-muted p-0">
                        <i class="bi bi-arrow-left"></i> Kembali ke Riwayat
                    </a>
                </div>

                <div class="card shadow-sm border-0">
                    <div class="card-header bg-dark text-white p-3">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0">Detail Modal & Profit</h5>
                            <span class="badge bg-warning text-dark">{{ $trx->invoice_no }}</span>
                        </div>
                    </div>
                    <div class="card-body p-4">
                        <div class="row mb-4">
                            <div class="col-6">
                                <p class="text-muted mb-1">Customer</p>
                                <h6>{{ $trx->customer_name }}</h6>
                            </div>
                            <div class="col-6 text-end">
                                <p class="text-muted mb-1">Total Penjualan</p>
                                <h4 class="text-success fw-bold">Rp {{ number_format($trx->total_sales, 0, ',', '.') }}</h4>
                            </div>
                        </div>

                        <form action="{{ route('history.update', $trx->id) }}" method="POST">
                            @csrf @method('PUT')
                            <div class="table-responsive">
                                <table class="table table-bordered align-middle">
                                    <thead class="table-light text-center">
                                        <tr>
                                            <th>Item Barang</th>
                                            <th style="width: 100px;">Qty</th>
                                            <th>Harga Jual</th>
                                            <th style="width: 250px;">Harga Beli (Satuan)</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($items as $item)
                                        <tr>
                                            <td><strong>{{ $item->product_name }}</strong></td>
                                            <td class="text-center">{{ $item->qty }}</td>
                                            <td class="text-center text-muted">Rp {{ number_format($item->price_sell, 0, ',', '.') }}</td>
                                            <td>
                                                <div class="input-group">
                                                    <span class="input-group-text">Rp</span>
                                                    <input type="number" name="items[{{ $item->id }}][price_buy]" 
                                                           value="{{ $item->price_buy }}" 
                                                           class="form-control fw-bold text-danger" 
                                                           placeholder="0" required>
                                                </div>
                                            </td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>

                            <div class="alert alert-info d-flex align-items-center mt-3" role="alert">
                                <i class="bi bi-info-circle-fill me-2"></i>
                                <div>
                                    Sistem akan otomatis menghitung <strong>Profit = Total Jual - (Harga Beli x Qty)</strong>.
                                </div>
                            </div>

                            <div class="mt-4 border-top pt-3 d-flex justify-content-end">
                                <button type="submit" class="btn btn-success btn-lg px-5">
                                    <i class="bi bi-check-circle"></i> Simpan & Update Profit
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>