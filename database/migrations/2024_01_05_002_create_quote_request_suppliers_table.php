<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quote_request_suppliers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->cascadeOnDelete();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('responded_at')->nullable();
            $table->enum('status', ['pendiente', 'respondido', 'declinado'])->default('pendiente');
            $table->unique(['quote_request_id', 'supplier_id'], 'uq_qr_supplier');
        });
    }
    public function down(): void { Schema::dropIfExists('quote_request_suppliers'); }
};
