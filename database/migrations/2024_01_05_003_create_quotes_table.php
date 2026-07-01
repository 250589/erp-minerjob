<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quotes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('code', 30)->nullable();
            $table->char('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->integer('payment_term_days')->default(0);
            $table->integer('delivery_term_days')->default(0);
            $table->date('validity_date')->nullable();
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->text('notes')->nullable();
            $table->string('file_path', 255)->nullable();
            $table->enum('status', ['recibida', 'comparada', 'aprobada', 'rechazada'])->default('recibida');
            $table->timestamp('received_at')->nullable()->useCurrent();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('quotes'); }
};
