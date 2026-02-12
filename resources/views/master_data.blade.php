<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Master Data Produk - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.5/font/bootstrap-icons.css">
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="d-flex justify-content-between align-items-center bg-white p-3 rounded shadow-sm mb-4">
            <h4 class="mb-0 text-success fw-bold"><i class="bi bi-box-seam"></i> Master Data</h4>
            <div>
                <a href="{{ route('invoice.index') }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-house"></i> Kembali ke Invoice
                </a>
                <a href="{{ route('history') }}" class="btn btn-outline-info btn-sm ms-2">
                    <i class="bi bi-clock-history"></i> Lihat Riwayat
                </a>
            </div>
        </div>

        <div class="row mt-4">
            <div class="col-md-4">
                <div class="card shadow-sm border-0 mb-4">
                    <div class="card-header bg-success text-white p-3">
                        <h6 class="mb-0"><i class="bi bi-plus-lg"></i> Tambah Produk Baru</h6>
                    </div>
                    <div class="card-body p-4">
                        <form action="{{ route('product.store') }}" method="POST">
                            @csrf
                            <div class="mb-3">
                                <label class="form-label small text-muted">Nama Produk</label>
                                <input type="text" name="name" class="form-control" placeholder="Contoh: Ikan Nila Segar" required autocomplete="off">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small text-muted">Harga Jual Default (Satuan)</label>
                                <div class="input-group">
                                    <span class="input-group-text">Rp</span>
                                    <input type="number" name="price" class="form-control" placeholder="0" required>
                                </div>
                                <div class="form-text text-muted" style="font-size: 0.75rem;">
                                    *Harga ini akan muncul otomatis saat pilih produk di invoice.
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success w-100 py-2">
                                <i class="bi bi-save"></i> Simpan ke Master
                            </button>
                        </form>
                    </div>
                </div>
            </div>

            <div class="col-md-8">
                <div class="card shadow-sm border-0 text-center">
                    <div class="card-header bg-dark text-white p-3">
                        <h6 class="mb-0 text-start"><i class="bi bi-list-ul"></i> Daftar Produk Tersedia</h6>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="text-start ps-4">Nama Produk</th>
                                        <th class="text-end">Harga Jual</th>
                                        <th class="text-center" style="width: 100px;">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse($products as $p)
                                    <tr>
                                        <td class="text-start ps-4 fw-bold text-dark">{{ $p->name }}</td>
                                        <td class="text-end fw-bold text-success">
                                            Rp {{ number_format($p->price, 0, ',', '.') }}
                                        </td>
                                        <td class="text-center">
                                            <form action="{{ route('product.delete', $p->id) }}" method="POST" onsubmit="return confirm('Hapus produk ini dari master?')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="btn btn-sm btn-outline-danger border-0">
                                                    <i class="bi bi-trash-fill"></i>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="3" class="py-5 text-muted">
                                            <i class="bi bi-info-circle display-6 d-block mb-3"></i>
                                            Belum ada produk di master data.
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</body>
</html>