<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transfer_reception_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_reception_id')->constrained('transfer_receptions')->cascadeOnDelete();
            $table->foreignId('transfer_order_item_id')->constrained('transfer_order_items')->restrictOnDelete();
            $table->decimal('quantity_received', 14, 4);
            $table->enum('condition_status', ['bueno', 'danado'])->default('bueno');
            $table->string('notes', 255)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('transfer_reception_items'); }
};
