<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Paso 39: Nota de Entrega (NE-YYYY-NNNN).
     * El subalmacén la genera cuando el personal solicita materiales.
     * status borrador → entregada al confirmar (Paso 41).
     */
    public function up(): void {
        Schema::create('delivery_notes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('warehouse_id')
                ->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()
                ->constrained('areas')->nullOnDelete();
            $table->foreignId('requirement_id')->nullable()
                ->constrained('requirements')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->foreignId('requested_by')
                ->constrained('users')->restrictOnDelete();
            $table->foreignId('delivered_by')->nullable()
                ->constrained('users')->nullOnDelete();
            $table->timestamp('delivered_at')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['borrador', 'entregada'])->default('borrador');
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('delivery_notes'); }
};