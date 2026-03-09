<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Summary Penjualan - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-success"><i class="bi bi-journal-check"></i> Summary Harian</h2>
            <a href="{{ route('history') }}" class="btn btn-outline-secondary">Kembali ke History</a>
        </div>

        <div class="card shadow-sm mb-4 border-0">
            <div class="card-body bg-white rounded">
                <form action="{{ route('report.summary') }}" method="GET" class="row g-3 align-items-end">
                    <div class="col-md-6">
                        <label class="form-label fw-bold text-muted">Pilih Tanggal (Hanya yang ada penjualan):</label>
                        <select name="date" class="form-select form-select-lg border-success" onchange="this.form.submit()">
                            @forelse($availableDates as $date)
                                <option value="{{ $date }}" {{ $selectedDate == $date ? 'selected' : '' }}>
                                    {{ date('d M Y', strtotime($date)) }} 
                                    @if($date == date('Y-m-d')) (Hari Ini) @endif
                                </option>
                            @empty
                                <option value="">Belum ada data penjualan</option>
                            @endforelse
                        </select>
                    </div>
                </form>
            </div>
        </div>

        <div class="card shadow border-0">
            <div class="card-header bg-success text-white py-3">
                <h5 class="mb-0">Laporan Detail Keuntungan: <strong>{{ date('d F Y', strtotime($selectedDate)) }}</strong></h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4 py-3">Jenis Sayuran</th>
                                <th class="text-center py-3">Qty</th>
                                <th class="text-end py-3">Omzet (Bruto)</th>
                                <th class="text-end py-3">Modal/Item</th> 
                                <th class="text-end pe-4 py-3">Laba Bersih</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($summary as $row)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $row->product_name }}</td>
                                <td class="text-center">
                                    <span class="badge rounded-pill bg-secondary px-3">{{ $row->total_qty }}</span>
                                </td>
                                <td class="text-end">
                                    Rp {{ number_format($row->total_revenue, 0, ',', '.') }}
                                </td>
                                <td class="text-end text-muted">
                                    Rp {{ number_format($row->total_buy, 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4 fw-bold text-success">
                                    Rp {{ number_format($row->total_profit, 0, ',', '.') }}
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">Belum ada data penjualan.</td>
                            </tr>
                            @endforelse
                        </tbody>
                        @if($summary->isNotEmpty())
                        <tfoot class="table-dark fw-bold fs-6">
                            <tr>
                                <td class="ps-4 py-3" colspan="2">TOTAL KESELURUHAN</td>
                                <td class="text-end py-3">
                                    Rp {{ number_format($summary->sum('total_revenue'), 0, ',', '.') }}
                                </td>
                                <td class="text-end py-3 text-info">
                                    Modal: Rp {{ number_format($summary->sum(fn($item) => $item->total_qty * $item->price_buy), 0, ',', '.') }}
                                </td>
                                <td class="text-end pe-4 py-3 text-warning">
                                    Laba: Rp {{ number_format($summary->sum('total_profit'), 0, ',', '.') }}
                                </td>
                            </tr>
                        </tfoot>
                        @endif
                    </table>
                </div>
            </div>
        </div>

    </div>
</body>
</html>