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
        $masterData = Product::orderBy('name', 'asc')->get();
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

    // Update Produk
    public function updateProduct(Request $request, $id)
    {
        $request->validate([
            'name' => 'required',
            'price' => 'required|numeric'
        ]);

        $product = Product::find($id);
        $product->update([
            'name' => $request->name,
            'price' => $request->price
        ]);

        return back()->with('success', 'Produk berhasil diperbarui');
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
            $totalCost += ((float)$item->qty * $priceBuy);
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

    public function reportSummary(Request $request)
    {
        $availableDates = DB::table('transactions')
            ->select(DB::raw('DATE(created_at) as date'))
            ->distinct()
            ->orderBy('date', 'desc')
            ->pluck('date');

        $selectedDate = $request->input('date', $availableDates->first() ?? date('Y-m-d'));

        $summary = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->select(
                'transaction_items.product_name',
                'transaction_items.price_buy',
                DB::raw('SUM(transaction_items.qty * transaction_items.price_buy) as total_buy'),
                DB::raw('SUM(transaction_items.qty) as total_qty'),
                DB::raw('SUM(transaction_items.qty * transaction_items.price_sell) as total_revenue'),
                DB::raw('SUM(transaction_items.qty * (transaction_items.price_sell - transaction_items.price_buy)) as total_profit')
            )
            ->whereDate('transactions.created_at', $selectedDate)
            ->groupBy('transaction_items.product_name', 'transaction_items.price_buy') // Tambahkan di group by
            ->having('total_qty', '>', 0)
            ->orderBy('total_profit', 'desc')
            ->get();

        return view('report_summary', compact('summary', 'selectedDate', 'availableDates'));
    }

    public function reportCharts()
    {
        // --- 1. DATA CARD (Bulan Ini) ---
        $currentMonth = date('Y-m');
        $matrix = DB::table('transactions')
            ->where(DB::raw("strftime('%Y-%m', created_at)"), $currentMonth)
            ->get();

        // --- 2. DATA GRAFIK OMZET (SQLite) ---
        $monthlyData = DB::table('transactions')
            ->select(
                DB::raw("strftime('%Y-%m', created_at) as month"),
                DB::raw('SUM(total_sales) as omzet'),
                DB::raw('SUM(profit) as profit')
            )
            ->where('created_at', '>=', now()->subMonths(6))
            ->groupBy('month')->orderBy('month', 'asc')->get();

        $monthlyOmzet = $monthlyData->pluck('omzet', 'month');
        $monthlyProfit = $monthlyData->pluck('profit', 'month');
        $allMonths = $monthlyData->pluck('month');

        // --- 3. DATA TREND ITEM ---
        $itemMonthlyTrendsData = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->select(
                'transaction_items.product_name',
                DB::raw("strftime('%Y-%m', transactions.created_at) as month"),
                DB::raw('SUM(transaction_items.qty) as total_qty')
            )
            ->where('transactions.created_at', '>=', now()->subMonths(6))
            ->groupBy('month', 'transaction_items.product_name')->get();

        $itemsData = [];
        foreach ($itemMonthlyTrendsData as $data) {
            $itemsData[$data->product_name][$data->month] = $data->total_qty;
        }

        $itemTrendDatasets = [];
        $index = 0;

        foreach ($itemsData as $name => $monthQtys) {
            $datasetData = [];
            foreach ($allMonths as $month) { 
                $datasetData[] = $monthQtys[$month] ?? 0; 
            }

            // GENERATE WARNA UNIK BERDASARKAN NAMA PRODUK
            // Kita pakai hash nama produk agar warnanya selalu sama untuk produk tersebut
            $hash = md5($name);
            $color = '#' . substr($hash, 0, 6); // Ambil 6 karakter pertama hash jadi kode HEX
            
            $itemTrendDatasets[] = [
                'label' => $name, 
                'data' => $datasetData,
                'borderColor' => $color,
                'backgroundColor' => $color,
                'fill' => false, 
                'tension' => 0.3
            ];
            $index++;
        }

        // --- 4. ENGINE AI PRO (HOLT-WINTERS SIMULATION) ---
        // Kita ambil data harian 30 hari terakhir untuk melihat pola WEEKEND vs WEEKDAY
        $dailySales = DB::table('transaction_items')
            ->join('transactions', 'transactions.id', '=', 'transaction_items.transaction_id')
            ->select(
                'transaction_items.product_name',
                DB::raw("strftime('%w', transactions.created_at) as day_of_week"), // 0=Minggu, 6=Sabtu
                DB::raw('SUM(transaction_items.qty) as qty')
            )
            ->where('transactions.created_at', '>=', now()->subDays(30))
            ->groupBy('product_name', 'day_of_week')
            ->get();

        $aiRecommendations = collect($itemsData)->map(function($monthValues, $productName) use ($dailySales) {
            $qtys = array_values($monthValues);
            $count = count($qtys);
            
            // --- Algoritma Dasar: Weighted Moving Average ---
            // Bulan terbaru bobotnya 3x lipat lebih penting dari bulan lama
            $weightedSum = 0; $weightTotal = 0;
            foreach ($qtys as $i => $q) {
                $w = $i + 1; $weightedSum += ($q * $w); $weightTotal += $w;
            }
            $basePredict = ($weightTotal > 0) ? $weightedSum / $weightTotal : 0;

            // --- Analisa Pola Weekend ---
            $itemDaily = $dailySales->where('product_name', $productName);
            $weekendBoost = $itemDaily->whereIn('day_of_week', ['0', '6'])->avg('qty') ?? 0;
            $weekdayAvg = $itemDaily->whereIn('day_of_week', ['1', '2', '3', '4', '5'])->avg('qty') ?? 0;
            
            $isWeekendPopular = ($weekendBoost > $weekdayAvg * 1.2);

            // --- Penentuan Keputusan AI ---
            $trendStatus = 'Stabil';
            $prediction = $basePredict;
            $reason = "Permintaan stabil berdasarkan pola 6 bulan terakhir.";

            if ($count >= 2) {
                $growth = ($qtys[$count-1] - $qtys[$count-2]);
                if ($growth > 0) {
                    $trendStatus = 'Naik';
                    $prediction = $basePredict + ($growth * 0.5); // Antisipasi kenaikan berkelanjutan
                    $reason = "Ada tren kenaikan bulanan. ";
                } elseif ($growth < 0) {
                    $trendStatus = 'Turun';
                    $prediction = $basePredict + ($growth * 0.3);
                    $reason = "Permintaan sedang melandai. ";
                }
            }

            if ($isWeekendPopular) {
                $reason .= "Hati-hati, item ini SANGAT laku di akhir pekan. Stok harus ready di hari Jumat.";
            }

            // Rekomendasi Tanam (Safety Stock 20%)
            $plantRec = round($prediction * 1.2);

            return (object)[
                'product_name' => $productName,
                'trend' => $trendStatus,
                'predicted_qty' => round($prediction),
                'recommended_to_plant' => $plantRec,
                'ai_reason' => $reason,
                'is_hot' => $isWeekendPopular
            ];
        })->sortByDesc('predicted_qty')->values();

        return view('report_charts', compact(
            'matrix', 'monthlyOmzet', 'monthlyProfit', 
            'itemTrendDatasets', 'allMonths', 'aiRecommendations'
        ));
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
        $cashierName = $request->input('cashier_name', '-');
        $itemsInput = $request->input('items', []); 
        
        // 1. Simpan ke DB dan Ambil ID-nya keluar
        $transactionId = DB::transaction(function () use ($noInvoice, $customerName, $cashierName, $itemsInput) {
            $totalSales = 0;
            $id = DB::table('transactions')->insertGetId([
                'invoice_no' => $noInvoice,
                'customer_name' => $customerName,
                'cashier_name' => $cashierName,
                'total_cost' => 0,
                'total_sales' => 0,
                'profit' => 0,
                'created_at' => now(), 'updated_at' => now(),
            ]);

            foreach ($itemsInput as $item) {
                // 1. Cari harga beli terakhir dari transaksi sebelumnya untuk produk yang sama
                $lastPriceBuy = DB::table('transaction_items')
                    ->where('product_name', $item['name'])
                    ->where('price_buy', '>', 0) // Pastikan bukan data kosong
                    ->latest('id')               // Ambil yang paling baru
                    ->value('price_buy');        // Ambil kolom price_buy saja

                // 2. Jika tidak ditemukan (produk baru), set ke 0 atau harga jual sementara
                $priceBuy = $lastPriceBuy ?? 0;

                $totalSales += ((float)$item['qty'] * $item['price']);
                
                DB::table('transaction_items')->insert([
                    'transaction_id' => $id,
                    'product_name' => $item['name'],
                    'qty' => $item['qty'],
                    'price_sell' => $item['price'],
                    'price_buy' => $priceBuy, // Sekarang otomatis terisi
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
        $printer->text("Kasir   : " . strtoupper($trx->cashier_name) . "\n");
        $printer->text(str_repeat('=', 32) . "\n");

        foreach ($items as $item) {
            $printer->text($this->wrapText($item->product_name, 32) . "\n");
            $subtotal = number_format((float)$item->qty * $item->price_sell, 0, ',', '.');
            $hargaSatuan = number_format($item->price_sell, 0, ',', '.');

            if (floor($item->qty) != $item->qty) {
                $formattedQty = (float)$item->qty . "kg"; 
            } else {
                $formattedQty = number_format($item->qty, 0) . "x";
            }
            $printer->text(sprintf(" %-7s %-8s %11s\n", $formattedQty, $hargaSatuan, $subtotal));
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
