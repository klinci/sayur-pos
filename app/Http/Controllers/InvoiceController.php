<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class InvoiceController extends Controller
{
    // Halaman Utama Invoice
    public function index()
    {
        $masterData = Product::all();
        return view('invoice', compact('masterData'));
    }

    // Halaman Master Data
    public function masterData()
    {
        $products = Product::orderBy('name', 'asc')->get();
        return view('master_data', compact('products'));
    }

    // Simpan Produk Baru
    public function storeProduct(Request $request)
    {
        Product::create($request->only('name', 'price'));
        return back()->with('success', 'Produk berhasil ditambah');
    }

    // Hapus Produk
    public function deleteProduct($id)
    {
        Product::destroy($id);
        return back()->with('success', 'Produk berhasil dihapus');
    }

    // --- RIWAYAT & PROFIT ---
    public function history() {
        $transactions = DB::table('transactions')->orderBy('created_at', 'desc')->get();
        return view('history', compact('transactions'));
    }
    public function editHistory($id) {
        $trx = DB::table('transactions')->where('id', $id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $id)->get();
        return view('edit_history', compact('trx', 'items'));
    }
    public function updateHistory(Request $request, $id) {
        $totalCost = 0;
        foreach ($request->items as $itemId => $val) {
            $priceBuy = $val['price_buy'] ?? 0;
            DB::table('transaction_items')->where('id', $itemId)->update(['price_buy' => $priceBuy]);
            $item = DB::table('transaction_items')->where('id', $itemId)->first();
            $totalCost += ($item->qty * $priceBuy);
        }
        $trx = DB::table('transactions')->where('id', $id)->first();
        DB::table('transactions')->where('id', $id)->update([
            'total_cost' => $totalCost,
            'profit' => $trx->total_sales - $totalCost
        ]);
        return redirect()->route('history');
    }

    public function deleteTransaction($id)
    {
        try {
            DB::transaction(function () use ($id) {
                // Hapus detailnya dulu (opsional jika cascade jalan, tapi buat jaga-jaga)
                DB::table('transaction_items')->where('transaction_id', $id)->delete();
                
                // Hapus induk transaksinya
                DB::table('transactions')->where('id', $id)->delete();
            });

            return back()->with('success', 'Transaksi berhasil dihapus selamanya.');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal menghapus data: ' . $e->getMessage());
        }
    }

    public function print(Request $request)
    {
        // Ambil waktu saat ini dengan zona waktu Indonesia (WIB)
        $now = \Carbon\Carbon::now()->timezone('Asia/Jakarta');
        $tanggalJam = $now->translatedFormat('d-m-Y H:i:s'); // Format: 12-02-2026 16:00:00

        // --- GENERATE NO INVOICE UNIK ---
        // Format: BRG (BaReng7) + Tanggal + JamMenitDetik
        $noInvoice = "BRG/" . $now->format('Ymd') . "/" . $now->format('His');

        // 1. Ambil data dari request
        $customerName = $request->input('customer_name', 'Pelanggan');        

        $itemsInput = $request->input('items', []); // Ambil array items
        
        // Hitung total secara otomatis
        $total = 0;
        foreach ($itemsInput as $item) {
            $total += ($item['qty'] * $item['price']);
        }

        // Simpan ke DB dulu
        DB::transaction(function () use ($noInvoice, $customerName, $itemsInput) {
            $totalSales = 0;
            $transactionId = DB::table('transactions')->insertGetId([
                'invoice_no' => $noInvoice,
                'customer_name' => $customerName,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($itemsInput as $item) {
                $totalSales += ($item['qty'] * $item['price']);
                DB::table('transaction_items')->insert([
                    'transaction_id' => $transactionId,
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'price_sell' => $item['price'],
                    'price_buy' => 0, // Belum diisi
                ]);
            }
            DB::table('transactions')->where('id', $transactionId)->update([
                'total_sales' => $totalSales, 'profit' => $totalSales
            ]);
        });

        $connector = new WindowsPrintConnector("POS-58");
        $printer   = new Printer($connector);

        $printer->initialize();
        $printer->setLineSpacing(33); // 🔧 sedikit lebih longgar
        $printer->setJustification(Printer::JUSTIFY_CENTER);

       
        // 🖼️ PRINT NV LOGO (TOP)
        // =========================
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $connector->write("\x1C\x70\x01\x00"); 
        $printer->feed(1);

        // ===== HEADER =====

        $printer->selectPrintMode(
            Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT
        );
        $printer->text("BaReng7 Vegie\n\n");
        $printer->selectPrintMode();
        $printer->text("WA: 895-3338-08937\n");

        // --- CETAK NO INVOICE & TANGGAL ---
        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $printer->text("No  : " . $noInvoice . "\n");
        $printer->text("Tgl : " . $tanggalJam . "\n");

        // --- TAMBAHAN NAMA CUSTOMER ---
        $printer->text("Customer: " . strtoupper($customerName) . "\n");
        // ------------------------------

        $printer->text(str_repeat('=', 32) . "\n");

        // ===== ITEMS =====
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        foreach ($itemsInput as $item) {
            // Nama item (item['name'])
            $printer->text($this->wrapText($item['name'], 32) . "\n");
            $printer->setLineSpacing(33);

            // Detail: Qty x Harga = Subtotal

            $subtotal = number_format($item['qty'] * $item['price'], 0, ',', '.');
            $hargaSatuan = number_format($item['price'], 0, ',', '.');

            $printer->text(sprintf(
                " %2dx %-8s %11s\n",
                $item['qty'],
                $hargaSatuan,
                $subtotal
            ));

            $printer->feed(1);
        }

        $printer->text(str_repeat('-', 32) . "\n");

        // ===== TOTAL =====
        $printer->setEmphasis(true);
        $printer->text(sprintf(
            "TOTAL %26s\n",
            $this->formatRupiah($total)
        ));
        $printer->setEmphasis(false);

        // ===== FOOTER =====
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->feed(1);
        $printer->text("Pembayaran dapat ditransfer\n ke rekening:\n\nBCA: 1560815600 a.n Merla Rosita\n\nThank you for your order\n");

        $printer->feed(4);
        $printer->cut();
        $printer->close();

        return response()->json(['status' => 'printed']);
    }

    public function reprint($id)
    {
        // 1. Ambil data transaksi dan itemnya
        $trx = DB::table('transactions')->where('id', $id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $id)->get();

        if (!$trx) {
            return back()->with('error', 'Transaksi tidak ditemukan');
        }

        $customerName = $trx->customer_name; 

        $tanggalFormatted = \Carbon\Carbon::parse($trx->created_at)
                        ->timezone('Asia/Jakarta')
                        ->translatedFormat('d-m-Y H:i:s');

        try {
            $connector = new WindowsPrintConnector("POS-58");
            $printer = new Printer($connector);
            
            $printer->initialize();            
            $printer->setLineSpacing(33); // 🔧 sedikit lebih longgar
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            
            // Logo (NV RAM)
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $connector->write("\x1C\x70\x01\x00"); 
            $printer->feed(1);

            /* ===== HEADER ===== */
            $printer->selectPrintMode(
            Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT
            );
            $printer->text("BaReng7 Vegie\n\n");
            $printer->selectPrintMode();
            $printer->text("WA: 895-3338-08937\n");

            $printer->setJustification(Printer::JUSTIFY_LEFT);
            $printer->text("No  : " . $trx->invoice_no . " (RP)\n");
            $printer->text("Tgl : " . $tanggalFormatted . "\n");
            $printer->text("Customer: " . strtoupper($customerName) . "\n");
            $printer->text(str_repeat('=', 32) . "\n");

            /* ===== ITEMS ===== */
            $printer->setJustification(Printer::JUSTIFY_LEFT);

            foreach ($items as $item) {
                $printer->text($this->wrapText($item->product_name, 32) . "\n");
                
                $printer->setLineSpacing(33);

                $subtotal = number_format($item->qty * $item->price_sell, 0, ',', '.');
                $hargaSatuan = number_format($item->price_sell, 0, ',', '.');

                $printer->text(sprintf(
                    " %2dx %-8s %11s\n",
                    $item->qty,
                    $hargaSatuan,
                    $subtotal
                ));

                $printer->feed(1);
            }

            $printer->text(str_repeat('-', 32) . "\n");

            /* ===== TOTAL ===== */
            $printer->setEmphasis(true);
            $printer->text(sprintf(
                "TOTAL %26s\n",
                $this->formatRupiah($trx->total_sales)
            ));
            $printer->setEmphasis(false);

            /* ===== FOOTER ===== */
            $printer->feed(1);
            $printer->setJustification(Printer::JUSTIFY_CENTER);
            $printer->feed(1);
            $printer->text("Pembayaran dapat ditransfer\n ke rekening:\n\nBCA: 1560815600 a.n Merla Rosita\n\nThank you for your order\n");
            $printer->feed(4);
            $printer->cut();
            $printer->close();

            return back()->with('success', 'Struk berhasil dicetak ulang!');
        } catch (\Exception $e) {
            return back()->with('error', 'Gagal print: ' . $e->getMessage());
        }
    }

    private function wrapText($text, $max)
    {
        return wordwrap($text, $max, "\n", true);
    }

    private function formatRupiah($angka)
    {
        return "Rp " . number_format($angka, 0, ',', '.');
    }
}
