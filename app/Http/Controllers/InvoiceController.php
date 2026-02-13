<?php

namespace App\Http\Controllers;

use App\Models\Product;
use Barryvdh\DomPDF\Facade\Pdf;
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

    // --- FUNGSI GENERATE PDF ---
    public function generatePDF($id)
    {
        $trx = DB::table('transactions')->where('id', $id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $id)->get();

        if (!$trx) return back();

        $pdf = Pdf::loadView('pdf_invoice', compact('trx', 'items'));
        return $pdf->download('Invoice-' . str_replace("/", "-", $trx->invoice_no) . '.pdf');
    }

    public function print(Request $request)
    {
        $now = \Carbon\Carbon::now()->timezone('Asia/Jakarta');
        $noInvoice = "BRG/" . $now->format('Ymd') . "/" . $now->format('His');
        $customerName = $request->input('customer_name', 'Pelanggan');        
        $itemsInput = $request->input('items', []); 
        
        // 1. Simpan ke DB dan Ambil ID-nya keluar
        $transactionId = DB::transaction(function () use ($noInvoice, $customerName, $itemsInput) {
            $totalSales = 0;
            $id = DB::table('transactions')->insertGetId([
                'invoice_no' => $noInvoice,
                'customer_name' => $customerName,
                'total_cost' => 0,
                'total_sales' => 0,
                'profit' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($itemsInput as $item) {
                $totalSales += ($item['qty'] * $item['price']);
                DB::table('transaction_items')->insert([
                    'transaction_id' => $id,
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'price_sell' => $item['price'],
                    'price_buy' => 0,
                ]);
            }

            DB::table('transactions')->where('id', $id)->update([
                'total_sales' => $totalSales, 
                'profit' => $totalSales
            ]);

            return $id; // Mengembalikan ID agar bisa dipakai di bawah
        });

        // 2. Cek Opsi Print
        $opsi = $request->input('print_option'); 
        $response = ['status' => 'success'];

        // Jika pilih Struk atau Keduanya, jalankan perintah printer thermal
        if ($opsi == 'struk' || $opsi == 'keduanya') {
            try {
                // Panggil reprint tapi lewat internal logic agar tidak redirect
                $this->executeThermalPrint($transactionId);
            } catch (\Exception $e) {
                $response['print_error'] = $e->getMessage();
            }
        }

        // Jika pilih PDF atau Keduanya, berikan URL PDF ke JavaScript
        if ($opsi == 'pdf' || $opsi == 'keduanya') {
            $response['pdf_url'] = route('history.pdf', $transactionId);
        }

        // 3. RETURN JSON (Ini tempatnya)
        return response()->json($response);
    }

    public function reprint($id)
    {
        try {
            // Cukup panggil fungsi logic yang sudah kita buat tadi
            $this->executeThermalPrint($id, true);

            return back()->with('success', 'Struk berhasil dicetak ulang!');
        } catch (\Exception $e) {
            // Jika printer error (mati/kabel lepas), tetap ada notifikasi gagal
            return back()->with('error', 'Gagal print: ' . $e->getMessage());
        }
    }

    private function executeThermalPrint($id, $isReprint = false) {
        $trx = DB::table('transactions')->where('id', $id)->first();
        $items = DB::table('transaction_items')->where('transaction_id', $id)->get();
        
        $now = \Carbon\Carbon::parse($trx->created_at)->timezone('Asia/Jakarta');
        $tanggalJam = $now->translatedFormat('d-m-Y H:i:s');

        $connector = new WindowsPrintConnector("POS-58");
        $printer   = new Printer($connector);

        $printer->initialize();
        $printer->setLineSpacing(33);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $connector->write("\x1C\x70\x01\x00"); // Logo
        $printer->feed(1);

        $printer->selectPrintMode(Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT);
        $printer->text("BaReng7 Vegie\n\n");
        $printer->selectPrintMode();
        $printer->text("WA: 895-3338-08937\n");
        $printer->feed(1);

        $printer->setJustification(Printer::JUSTIFY_LEFT);
        $tanda = $isReprint ? " (RP)" : "";
        $printer->text("No  : " . $trx->invoice_no . $tanda . "\n");
        $printer->text("Tgl : " . $tanggalJam . "\n");
        $printer->text("Customer: " . strtoupper($trx->customer_name) . "\n");
        $printer->text(str_repeat('=', 32) . "\n");

        foreach ($items as $item) {
            $printer->text($this->wrapText($item->product_name, 32) . "\n");
            $subtotal = number_format($item->qty * $item->price_sell, 0, ',', '.');
            $hargaSatuan = number_format($item->price_sell, 0, ',', '.');
            $printer->text(sprintf(" %2dx %-8s %11s\n", $item->qty, $hargaSatuan, $subtotal));
            $printer->feed(1);
        }

        $printer->text(str_repeat('-', 32) . "\n");
        $printer->setEmphasis(true);
        $printer->text(sprintf("TOTAL %26s\n", $this->formatRupiah($trx->total_sales)));
        $printer->setEmphasis(false);
        $printer->text(str_repeat('-', 32) . "\n");
        
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Pembayaran dapat ditransfer\nke rekening:\n\nBCA: 1560815600 a.n Merla Rosita\n\nThank you for your order\n");
        $printer->feed(4);
        $printer->cut();
        $printer->close();
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
