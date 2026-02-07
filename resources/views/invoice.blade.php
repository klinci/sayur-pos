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
            <div class="card-header bg-success text-white">
                <h4 class="mb-0">Form Invoice BaReng7 Vegie</h4>
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
                                <input type="text" name="items[0][name]" class="form-control" placeholder="Nama Item" required>
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
                    <input type="text" name="items[${itemCount}][name]" class="form-control" placeholder="Nama Item" required>
                </div>
                <div class="col-md-2">
                    <input type="number" name="items[${itemCount}][qty]" class="form-control input-qty" placeholder="Qty" min="1" value="1" required>
                </div>
                <div class="col-md-3">
                    <input type="number" name="items[${itemCount}][price]" class="form-control input-price" placeholder="Harga" required>
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

                // 2. Hapus semua baris item kecuali baris pertama
                const rows = document.querySelectorAll('.item-row');
                rows.forEach((row, index) => {
                    if (index === 0) {
                        // Reset baris pertama ke awal
                        row.querySelector('input[name="items[0][name]"]').value = '';
                        row.querySelector('input[name="items[0][qty]"]').value = 1;
                        row.querySelector('input[name="items[0][price]"]').value = '';
                    } else {
                        // Hapus baris lainnya
                        row.remove();
                    }
                });

                // 3. Reset counter item ke 1
                itemCount = 1;

                // 4. Update tombol hapus dan total
                updateRemoveButtons();
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
                if(result.status === 'printed') {
                    alert('Invoice berhasil dicetak!');
                    // Opsional: Reset form setelah cetak
                    // e.target.reset();
                    // calculateTotal();
                }
            } catch (error) {
                alert('Gagal mencetak: ' + error);
            } finally {
                btn.disabled = false;
                btn.innerText = 'CETAK INVOICE';
            }
        });
    </script>
</body>
</html>