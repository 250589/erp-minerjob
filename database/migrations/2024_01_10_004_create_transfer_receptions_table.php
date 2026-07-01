<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('transfer_receptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('transfer_order_id')->constrained('transfer_orders')->cascadeOnDelete();
            $table->foreignId('received_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('received_at')->nullable()->useCurrent();
            $table->enum('status', ['aceptado', 'observado'])->default('aceptado');
            $table->text('observations')->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('transfer_receptions'); }
};
