<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transfer_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_requested', 14, 4);
            $table->decimal('quantity_sent', 14, 4)->nullable();
            $table->decimal('quantity_received', 14, 4)->nullable();
            $table->decimal('unit_cost', 14, 4)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('transfer_order_items'); }
};
