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
        $printer->text("TOKO CONTOH\n");
        $printer->selectPrintMode();

        $printer->text("Jl. Testing No. 143\n");
        $printer->feed(1);

        $printer->text(str_repeat('=', 32) . "\n");

        /* ===== ITEMS ===== */
        $printer->setJustification(Printer::JUSTIFY_LEFT);

        $items = [
            ['Ikan Segar Besar', 2, 10000],
            ['Nasi Putih', 3, 5000],
        ];

        foreach ($items as $item) {
            // Nama item
            $printer->text($this->wrapText($item[0], 32) . "\n");

            // Spasi kecil sebelum detail harga
            $printer->feed(1);

            // Qty x Harga = Subtotal
            $printer->text(sprintf(
                "  %2d x %-8s %12s\n",
                $item[1],
                number_format($item[2]),
                number_format($item[1] * $item[2])
            ));

            // 🔥 INI KUNCI: JARAK ANTAR ITEM
            $printer->feed(1);
        }

        $printer->text(str_repeat('-', 32) . "\n");

        /* ===== TOTAL ===== */
        $printer->setEmphasis(true);
        $printer->text(sprintf(
            "TOTAL %21s\n",
            number_format(35000)
        ));
        $printer->setEmphasis(false);

        /* ===== FOOTER ===== */
        $printer->feed(1);
        $printer->setJustification(Printer::JUSTIFY_CENTER);
        $printer->text("Terima kasih\n");

        $printer->feed(4);
        $printer->cut();
        $printer->close();

        return response()->json(['status' => 'printed']);
    }

    private function wrapText($text, $max)
    {
        return wordwrap($text, $max, "\n", true);
    }
}
