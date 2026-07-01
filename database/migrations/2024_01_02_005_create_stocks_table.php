<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('stocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->decimal('quantity', 14, 4)->default(0);
            $table->decimal('average_cost', 14, 4)->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();
            $table->unique(['warehouse_id', 'product_id'], 'uq_stock_warehouse_product');
        });
    }
    public function down(): void { Schema::dropIfExists('stocks'); }
};
