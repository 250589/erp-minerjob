<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('warehouse_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_reception_id')->constrained('warehouse_receptions')->cascadeOnDelete();
            $table->foreignId('purchase_order_item_id')->nullable()->constrained('purchase_order_items')->nullOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_ordered', 14, 4)->default(0);
            $table->decimal('quantity_received', 14, 4);
            $table->decimal('unit_purchase_price', 14, 4);
            $table->decimal('markup_percentage_applied', 6, 2)->default(35.00);
            $table->decimal('unit_sale_price', 14, 4);
            $table->enum('condition_status', ['bueno', 'danado'])->default('bueno');
            $table->string('notes', 255)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('warehouse_reception_items'); }
};
