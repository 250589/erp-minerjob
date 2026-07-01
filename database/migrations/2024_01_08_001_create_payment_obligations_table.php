<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payment_obligations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')->constrained('invoices')->restrictOnDelete();
            $table->foreignId('accounting_entry_id')->nullable()->constrained('accounting_entries')->nullOnDelete();
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('PEN');
            $table->date('due_date')->nullable();
            $table->enum('status', ['pendiente', 'pagado', 'vencido'])->default('pendiente');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('payment_obligations'); }
};
