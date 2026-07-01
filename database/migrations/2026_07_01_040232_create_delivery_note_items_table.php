<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * unit_cost: costo promedio del subalmacén congelado al momento de la entrega.
     * quantity_delivered: puede diferir de quantity_requested (entrega parcial).
     */
    public function up(): void {
        Schema::create('delivery_note_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('delivery_note_id')
                ->constrained('delivery_notes')->cascadeOnDelete();
            $table->foreignId('product_id')
                ->constrained('products')->restrictOnDelete();
            $table->decimal('quantity_requested', 14, 4);
            $table->decimal('quantity_delivered', 14, 4)->nullable()
                ->comment('Paso 40: cantidad efectivamente entregada');
            $table->decimal('unit_cost', 14, 4)->nullable()
                ->comment('Costo promedio congelado al entregar');
            $table->string('notes', 255)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('delivery_note_items'); }
};