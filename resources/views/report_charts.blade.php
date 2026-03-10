<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Grafik & Analisa AI - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.0/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        /* Tinggi kontainer dinaikkan agar chart lebih lega */
        .chart-container { position: relative; height:400px; width:100%; }
        .card { border-radius: 15px; }
        .fw-bold-qty { font-weight: bold; color: #198754; }
        .bg-gradient-success { background: linear-gradient(45deg, #198754, #28a745); }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2 class="fw-bold text-success"><i class="bi bi-bar-chart-line-fill"></i> Dashboard Analisa & AI</h2>
            <a href="{{ route('history') }}" class="btn btn-outline-secondary btn-sm">
                <i class="bi bi-arrow-left"></i> Kembali ke History
            </a>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-md-4">
                <div class="card shadow border-0 bg-gradient-success text-white">
                    <div class="card-body p-4">
                        <h6 class="text-uppercase opacity-75">Omzet Bulan Ini</h6>
                        <h2 class="fw-bold">Rp {{ number_format($matrix->sum('total_sales'), 0, ',', '.') }}</h2>
                        <small><i class="bi bi-calendar-event"></i> Periode: {{ date('F Y') }}</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow border-0 bg-white">
                    <div class="card-body p-4 border-start border-warning border-5 rounded">
                        <h6 class="text-uppercase text-muted">Laba Bersih</h6>
                        <h2 class="fw-bold text-success">Rp {{ number_format($matrix->sum('profit'), 0, ',', '.') }}</h2>
                        <small class="text-muted">Keuntungan murni bulan ini</small>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card shadow border-0 bg-white">
                    <div class="card-body p-4 border-start border-info border-5 rounded">
                        <h6 class="text-uppercase text-muted">Item Terjual (Qty)</h6>
                        <h2 class="fw-bold text-info">{{ number_format($matrix->count() > 0 ? $matrix->count() : 0, 0, ',', '.') }} Transaksi</h2>
                        <small class="text-muted">Total aktivitas penjualan</small>
                    </div>
                </div>
            </div>
        </div>

        <div class="row mb-4 g-3">
            <div class="col-md-6">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-activity text-success"></i> Tren Omzet vs Laba (6 Bulan)</h5>
                    </div>
                    <div class="card-body">
                        <div class="chart-container">
                            <canvas id="mainChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-6 text-center">
                <div class="card shadow border-0 h-100">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0 fw-bold"><i class="bi bi-pie-chart-fill text-warning"></i> Proporsi Prediksi</h5>
                    </div>
                    <div class="card-body d-flex align-items-center justify-content-center">
                        <div class="chart-container" style="height: 450px;"> <canvas id="itemPieChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="bi bi-box-seam text-primary"></i> Tren Penjualan Per Jenis Sayuran (Qty)</h5>
            </div>
            <div class="card-body">
                <div class="chart-container">
                    <canvas id="itemTrendChart"></canvas>
                </div>
            </div>
        </div>

        <div class="card shadow border-0 overflow-hidden">
            <div class="card-header bg-dark text-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold"><i class="bi bi-robot"></i> Rekomendasi Tanam & Produksi (Analisa AI)</h5>
                <span class="badge bg-success">AI Intellect Active</span>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-4">Sayuran</th>
                                <th class="text-center">Tren</th>
                                <th class="text-center">Estimasi Demand</th>
                                <th class="text-center text-success">Saran Tanam</th>
                                <th>Analisa AI</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($aiRecommendations as $rec)
                            <tr>
                                <td class="ps-4 fw-bold text-dark">{{ $rec->product_name }}</td>
                                <td class="text-center">
                                    @if($rec->trend == 'Naik') 
                                        <span class="badge bg-success"><i class="bi bi-graph-up-arrow"></i> Naik</span>
                                    @elseif($rec->trend == 'Turun') 
                                        <span class="badge bg-danger"><i class="bi bi-graph-down-arrow"></i> Turun</span>
                                    @else 
                                        <span class="badge bg-secondary">Stabil</span> 
                                    @endif
                                    
                                    @if($rec->is_hot)
                                        <span class="badge bg-warning text-dark d-block mt-1"><i class="bi bi-fire"></i> Weekend Hot</span>
                                    @endif
                                </td>
                                <td class="text-center">{{ $rec->predicted_qty }} unit</td>
                                <td class="text-center fw-bold text-success fs-5">{{ $rec->recommended_to_plant }}</td>
                                <td class="text-muted" style="max-width: 300px;"><small>{{ $rec->ai_reason }}</small></td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-clipboard-x fs-1 text-muted"></i>
                                    <p class="mt-2">Belum ada data prediksi. Pastikan tabel 'predictions' sudah terisi.</p>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script>
        const chartOptions = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: true,
                    position: 'bottom',
                    labels: {
                        usePointStyle: true,
                        padding: 20,
                        font: { size: 12 }
                    }
                }
            }
        };

        // Grafik 1: Omzet & Laba
        new Chart(document.getElementById('mainChart'), {
            type: 'line',
            data: {
                labels: {!! json_encode($monthlyOmzet->keys()) !!},
                datasets: [{
                    label: 'Omzet',
                    data: {!! json_encode($monthlyOmzet->values()) !!},
                    borderColor: '#198754', backgroundColor: 'rgba(25, 135, 84, 0.1)', fill: true, tension: 0.4
                }, {
                    label: 'Laba Bersih',
                    data: {!! json_encode($monthlyProfit->values()) !!},
                    borderColor: '#ffc107', backgroundColor: 'transparent', borderDash: [5, 5], tension: 0.4
                }]
            },
            options: chartOptions
        });

        // Grafik 2: Pie Prediksi (MODIFIKASI: Diperbesar dan lebih interaktif)
        // Grafik 2: Pie Prediksi (Warna Dinamis Unik)
        new Chart(document.getElementById('itemPieChart'), {
            type: 'doughnut',
            data: {
                labels: {!! json_encode($aiRecommendations->pluck('product_name')) !!},
                datasets: [{
                    data: {!! json_encode($aiRecommendations->pluck('predicted_qty')) !!},
                    // AMBIL WARNA DARI DATASET TREND (AGAR WARNA ITEM KONSISTEN DI SEMUA GRAFIK)
                    backgroundColor: {!! json_encode($itemTrendDatasets) !!}.map(d => d.backgroundColor),
                    hoverOffset: 30 
                }]
            },
            options: {
                ...chartOptions,
                cutout: '50%',
                plugins: {
                    ...chartOptions.plugins,
                    tooltip: {
                        padding: 15,
                        bodyFont: { size: 14 },
                        titleFont: { size: 15 }
                    }
                }
            }
        });

        // Grafik 3: Trend Per Item
        new Chart(document.getElementById('itemTrendChart'), {
            type: 'bar',
            data: {
                labels: {!! json_encode($allMonths) !!},
                datasets: {!! json_encode($itemTrendDatasets) !!}
            },
            options: {
                ...chartOptions,
                scales: {
                    y: { beginAtZero: true, grid: { display: false } },
                    x: { grid: { display: false } }
                }
            }
        });
    </script>
</body>
</html>