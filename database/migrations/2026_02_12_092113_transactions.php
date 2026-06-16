<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 2. Tabel Induk Transaksi
        Schema::create('transactions', function (Blueprint $table) {
            $table->id();
            $table->string('invoice_no')->unique();
            $table->string('customer_name');
            $table->integer('total_sales')->default(0);
            $table->integer('total_cost')->default(0); // Diisi setelah input harga beli manual
            $table->integer('profit')->default(0);
            $table->timestamps();
        });

        // 3. Tabel Detail Item
        Schema::create('transaction_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transaction_id')->constrained()->onDelete('cascade');
            $table->string('product_name');
            $table->float('qty');
            $table->integer('price_sell');
            $table->integer('price_buy')->default(0); // MANUAL INPUT DI RIWAYAT
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transaction_items');
        Schema::dropIfExists('transactions');
    }
};
