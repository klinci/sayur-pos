<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Input Invoice - BaReng7 Vegie</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .total-display {
            font-size: 1.5rem;
            font-weight: bold;
            color: #198754;
        }
    </style>
</head>
<body class="bg-light">
    <div class="container py-5">
        <div class="card shadow-sm">
            <div class="card-header bg-success text-white d-flex justify-content-between align-items-center p-3">
                <h4 class="mb-0 fw-bold">BaReng7 Vegie</h4>
                
                <div class="d-flex gap-2">
                    <a href="{{ route('product.master') }}" class="btn btn-light btn-sm fw-bold">
                        <i class="bi bi-box"></i> Master Data
                    </a>
                    <a href="{{ route('history') }}" class="btn btn-info btn-sm text-white fw-bold">
                        <i class="bi bi-clock-history"></i> History
                    </a>
                </div>
            </div>
            <div class="card-body">
                <form id="invoiceForm">
                    @csrf
                    <div class="mb-3">
                        <label class="form-label">Nama Customer</label>
                        <input type="text" name="customer_name" class="form-control" placeholder="Masukkan nama..." required>
                    </div>

                    <hr>
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h5 class="mb-0">Daftar Item</h5>
                        <button type="button" class="btn btn-outline-primary btn-sm" id="addItem">
                            + Tambah Item
                        </button>
                    </div>

                    <div id="itemContainer">
                        <div class="row g-2 mb-2 item-row">
                            <div class="col-md-5">
                                <select name="items[0][name]" class="form-select select-product" required>
                                    <option value="" data-price="0">-- Pilih Produk --</option>
                                    @foreach($masterData as $product)
                                        <option value="{{ $product['name'] }}" data-price="{{ $product['price'] }}">
                                            {{ $product['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                                <input type="hidden" name="total_hidden" id="total_hidden">
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[0][qty]" class="form-control input-qty" placeholder="Qty" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="items[0][price]" class="form-control input-price" placeholder="Harga" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 btn-remove" disabled>×</button>
                            </div>
                        </div>
                    </div>

                    <div class="mt-4 p-3 bg-light border rounded d-flex justify-content-between align-items-center">
                        <span class="h5 mb-0">Estimasi Total:</span>
                        <span class="total-display" id="grandTotalDisplay">Rp 0</span>
                    </div>

                    <div class="mb-3 p-3 border rounded bg-white">
                        <label class="form-label d-block fw-bold">Pilih Metode Cetak:</label>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="print_option" value="struk" checked>
                            <label class="form-check-label">Hanya Struk (Printer)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="print_option" value="pdf">
                            <label class="form-check-label">Hanya PDF (Digital)</label>
                        </div>
                        <div class="form-check form-check-inline">
                            <input class="form-check-input" type="radio" name="print_option" value="keduanya">
                            <label class="form-check-label">Keduanya</label>
                        </div>
                    </div>

                    <div class="mt-4 d-flex gap-2">
                        <button type="button" class="btn btn-outline-secondary btn-lg w-50" id="btnClear">
                            HAPUS SEMUA
                        </button>
                        <button type="submit" class="btn btn-success btn-lg w-50" id="btnPrint">
                            CETAK INVOICE
                        </button>
                    </div>
                </form>
            </div>
        </div>        
        <!--
                <b>Harga Jual:</b>
                <br/>
                - Kangkung 500 gram = 15000
                <br/>
                - Packoy 500 gram = 15000
                <br/>
                - Siomak 250 gram = 10000
                <br/>
                - Lolorosa 250 gram = 10000
                <br/>
                - Selada keriting 500 gram = 15000
                <br/>
                - Pagoda 250 gram = 10000
                <br/>
                - Naibai 250 gram = 12500
                <br/>
                - Bayam 250 gram = 10000
                <br/>
                - Tomat ceri 250 gram = 10000
        -->
    </div>

    <script>
        let itemCount = 1;

        // Fungsi Format Rupiah untuk Tampilan
        function formatRupiah(angka) {
            return "Rp " + new Intl.NumberFormat('id-ID').format(angka);
        }

        // Fungsi Hitung Total
        function calculateTotal() {
            let total = 0;
            const rows = document.querySelectorAll('.item-row');
            
            rows.forEach(row => {
                const qty = row.querySelector('.input-qty').value || 0;
                const price = row.querySelector('.input-price').value || 0;
                total += (parseInt(qty) * parseInt(price));
            });

            document.getElementById('grandTotalDisplay').innerText = formatRupiah(total);
        }

        // Listener untuk input (Qty & Harga)
        document.getElementById('itemContainer').addEventListener('input', (e) => {
            if (e.target.classList.contains('input-qty') || e.target.classList.contains('input-price')) {
                calculateTotal();
            }
        });

        // Tambah Item
        document.getElementById('addItem').addEventListener('click', () => {
            const container = document.getElementById('itemContainer');
            const row = document.createElement('div');
            row.className = 'row g-2 mb-2 item-row';
            row.innerHTML = `
                            <div class="col-md-5">
                                <select name="items[${itemCount}][name]" class="form-select select-product" required>
                                    <option value="" data-price="0">-- Pilih Produk --</option>
                                    @foreach($masterData as $product)
                                        <option value="{{ $product['name'] }}" data-price="{{ $product['price'] }}">
                                            {{ $product['name'] }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-2">
                                <input type="number" name="items[${itemCount}][qty]" class="form-control input-qty" min="1" value="1" required>
                            </div>
                            <div class="col-md-3">
                                <input type="number" name="items[${itemCount}][price]" class="form-control input-price" required>
                            </div>
                            <div class="col-md-2">
                                <button type="button" class="btn btn-danger w-100 btn-remove">×</button>
                            </div>
                        `;
            container.appendChild(row);
            itemCount++;
            updateRemoveButtons();
            calculateTotal(); // Re-calculate
        });

        // Hapus Item
        document.getElementById('itemContainer').addEventListener('click', (e) => {
            if (e.target.classList.contains('btn-remove')) {
                e.target.closest('.item-row').remove();
                updateRemoveButtons();
                calculateTotal(); // Re-calculate
            }
        });

        function updateRemoveButtons() {
            const rows = document.querySelectorAll('.item-row');
            rows.forEach(row => {
                row.querySelector('.btn-remove').disabled = (rows.length === 1);
            });
        }

        // Fungsi Reset Form
        document.getElementById('btnClear').addEventListener('click', () => {
            if (confirm('Apakah Anda yakin ingin menghapus semua data inputan?')) {
                // 1. Reset Nama Customer
                document.querySelector('input[name="customer_name"]').value = '';

                // 2. Ambil container dan semua baris
                const container = document.getElementById('itemContainer');
                const rows = container.querySelectorAll('.item-row');

                // 3. Reset baris pertama, hapus sisanya
                rows.forEach((row, index) => {
                    if (index === 0) {
                        // Reset Dropdown ke pilihan pertama (pilih produk)
                        const select = row.querySelector('.select-product');
                        select.selectedIndex = 0;
                        
                        // Kosongkan input Qty (kembali ke 1) dan Harga
                        row.querySelector('.input-qty').value = 1;
                        row.querySelector('.input-price').value = '';
                    } else {
                        // Hapus baris tambahan
                        row.remove();
                    }
                });

                // 4. Reset hitungan ID item kembali ke 1
                itemCount = 1;

                // 5. Update tampilan tombol hapus (agar baris pertama disable tombol x-nya)
                updateRemoveButtons();

                // 6. Reset Total secara visual dan hidden input
                document.getElementById('grandTotalDisplay').innerText = "Rp 0";
                if(document.getElementById('total_hidden')) {
                    document.getElementById('total_hidden').value = 0;
                }
                
                // Opsional: jalankan hitung ulang untuk memastikan
                calculateTotal();
            }
        });

        // Handle Submit (AJAX)
        document.getElementById('invoiceForm').addEventListener('submit', async (e) => {
            e.preventDefault();
            const btn = document.getElementById('btnPrint');
            btn.disabled = true;
            btn.innerText = 'Mencetak...';

            const formData = new FormData(e.target);
            
            try {
                const response = await fetch("{{ route('invoice.print') }}", {
                    method: 'POST',
                    body: formData,
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                
                const result = await response.json();
                if(result.status === 'success' || result.status === 'printed') {
                    if(result.pdf_url) {
                        window.open(result.pdf_url, '_blank');
                    }

                    alert('Invoice berhasil diproses!');
                    // Opsional: Reset form setelah cetak
                    // e.target.reset();
                    // calculateTotal();
                }
            } catch (error) {
                aalert('Gagal memproses transaksi: ' + error);
            } finally {
                btn.disabled = false;
                btn.innerText = 'CETAK INVOICE';
            }
        });

        document.getElementById('itemContainer').addEventListener('change', (e) => {
            if (e.target.classList.contains('select-product')) {
                // Ambil baris terdekat
                const row = e.target.closest('.item-row');
                // Ambil harga dari atribut data-price di option yang dipilih
                const selectedOption = e.target.options[e.target.selectedIndex];
                const price = selectedOption.getAttribute('data-price');
                
                // Isi input harga
                row.querySelector('.input-price').value = price;
                
                // Hitung ulang total
                calculateTotal();
            }
        });
    </script>
</body>
</html>