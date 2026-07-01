<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('kardex_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('product_id')->constrained('products')->restrictOnDelete();
            $table->enum('movement_type', ['ingreso_compra','salida_traslado','entrada_traslado','salida_entrega','ajuste_positivo','ajuste_negativo']);
            $table->string('reference_type', 60);
            $table->unsignedBigInteger('reference_id');
            $table->decimal('quantity', 14, 4);
            $table->decimal('unit_cost', 14, 4);
            $table->decimal('balance_quantity', 14, 4);
            $table->decimal('balance_value', 14, 4);
            $table->dateTime('movement_date');
            $table->string('notes', 255)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index(['warehouse_id', 'product_id'], 'idx_kardex_wh_product');
            $table->index(['reference_type', 'reference_id'], 'idx_kardex_reference');
        });
    }
    public function down(): void { Schema::dropIfExists('kardex_movements'); }
};
