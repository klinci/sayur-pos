<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Mike42\Escpos\Printer;
use Mike42\Escpos\PrintConnectors\WindowsPrintConnector;

class InvoiceController extends Controller
{
    public function index()
    {
        return view('invoice');
    }

    public function print(Request $request)
    {
        // 1. Ambil data dari request
        $customerName = $request->input('customer_name', 'Pelanggan');
        $itemsInput = $request->input('items', []); // Ambil array items
        
        // Hitung total secara otomatis
        $total = 0;
        foreach ($itemsInput as $item) {
            $total += ($item['qty'] * $item['price']);
        }

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

         /* ===== HEADER ===== */
        // =========================

        $printer->selectPrintMode(
            Printer::MODE_DOUBLE_WIDTH | Printer::MODE_DOUBLE_HEIGHT
        );
        $printer->text("BaReng7 Vegie\n\n");
        $printer->selectPrintMode();

        $printer->text("WA: 895-3338-08937\n");
        $printer->feed(1);

        // --- TAMBAHAN NAMA CUSTOMER ---
        $printer->text("Customer: " . strtoupper($customerName) . "\n");
        // ------------------------------

        $printer->text(str_repeat('=', 32) . "\n");

        /* ===== ITEMS ===== */
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

        /* ===== TOTAL ===== */
        $printer->setEmphasis(true);
        $printer->text(sprintf(
            "TOTAL %26s\n",
            $this->formatRupiah($total)
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

        return response()->json(['status' => 'printed']);
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
