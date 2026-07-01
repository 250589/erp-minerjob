<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transfer_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('origin_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('destination_warehouse_id')->constrained('warehouses')->restrictOnDelete();
            $table->foreignId('requested_by')->constrained('users')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->enum('status', ['creada', 'en_transito', 'recibida', 'rechazada'])->default('creada');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('transfer_orders'); }
};
