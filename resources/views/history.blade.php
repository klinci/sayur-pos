<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Riwayat Transaksi - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <nav aria-label="breadcrumb" class="mb-4">
            <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm">
                <h4 class="mb-0 text-success fw-bold">BaReng7 Vegie</h4>
                <div>
                    <a href="{{ route('invoice.index') }}" class="btn btn-outline-success btn-sm me-2">
                        <i class="bi bi-plus-circle"></i> Buat Invoice
                    </a>
                    <a href="{{ route('product.master') }}" class="btn btn-outline-primary btn-sm">
                        <i class="bi bi-box"></i> Master Data
                    </a>
                </div>
            </div>
        </nav>

        @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <div class="d-flex align-items-center">
                    <i class="bi bi-check-circle-fill me-2"></i> 
                    <div>{{ session('success') }}</div>
                </div>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        @if(session('error'))
            <div class="alert alert-danger alert-dismissible fade show shadow-sm border-0 mb-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        @endif

        <div class="card shadow-sm border-0">
            <div class="card-header bg-success text-white p-3">
                <h5 class="mb-0"><i class="bi bi-clock-history"></i> Riwayat Transaksi & Laba Rugi</h5>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Tanggal</th>
                                <th>No. Invoice</th>
                                <th>Customer</th>
                                <th class="text-end">Total Jual</th>
                                <th class="text-end">Profit</th>
                                <th class="text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($transactions as $t)
                            <tr>
                                <td class="ps-3">
                                    <small class="text-muted">{{ \Carbon\Carbon::parse($t->created_at)->format('d M Y') }}</small><br>
                                    <strong>{{ \Carbon\Carbon::parse($t->created_at)->format('H:i') }}</strong>
                                </td>
                                <td><span class="badge bg-secondary">{{ $t->invoice_no }}</span></td>
                                <td>{{ $t->customer_name }}</td>
                                <td class="text-end fw-bold">Rp {{ number_format($t->total_sales, 0, ',', '.') }}</td>
                                <td class="text-end">
                                    @if($t->total_cost == 0)
                                        <span class="text-warning small italic">Belum input modal</span>
                                    @else
                                        <span class="fw-bold {{ $t->profit >= 0 ? 'text-success' : 'text-danger' }}">
                                            Rp {{ number_format($t->profit, 0, ',', '.') }}
                                        </span>
                                    @endif
                                </td>
                                <td class="text-center">
                                    <div class="d-flex justify-content-center gap-1">
                                        
                                        <a href="{{ route('history.reprint', $t->id) }}" class="btn btn-sm btn-info text-white" title="Cetak Ulang">
                                            <i class="bi bi-printer"></i>
                                        </a>
                                        
                                        <a href="{{ route('history.edit', $t->id) }}" class="btn btn-sm btn-warning text-dark" title="Input Modal">
                                            Hitung Profit
                                        </a>
                                        
                                        <form action="{{ route('history.delete', $t->id) }}" method="POST" class="d-inline" id="delete-form-{{ $t->id }}">
                                            @csrf 
                                            @method('DELETE')
                                            <button type="button" class="btn btn-sm btn-outline-danger" onclick="confirmDelete('{{ $t->id }}')" title="Hapus">
                                                <i class="bi bi-trash"></i>
                                            </button>
                                        </form>

                                    </div>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">Belum ada transaksi hari ini.</td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            if(confirm('Apakah Bos yakin mau hapus transaksi ini? Data profit akan hilang permanent.')) {
                document.getElementById('delete-form-' + id).submit();
            }
        }

        document.querySelectorAll('.btn-info').forEach(button => {
            button.addEventListener('click', function() {
                if(this.innerHTML.includes('bi-printer')) {
                    this.classList.add('disabled');
                    this.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
                }
            });
        });
    </script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>