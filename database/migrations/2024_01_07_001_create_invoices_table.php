<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('purchase_order_id')->constrained('purchase_orders')->restrictOnDelete();
            $table->foreignId('supplier_id')->constrained('suppliers')->restrictOnDelete();
            $table->string('series', 10)->nullable();
            $table->string('number', 20);
            $table->date('issue_date');
            $table->char('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->decimal('subtotal', 14, 2)->default(0);
            $table->decimal('tax', 14, 2)->default(0);
            $table->decimal('total', 14, 2)->default(0);
            $table->enum('status', ['recibida','en_revision','observada','validada','registrada','pagada'])->default('recibida');
            $table->string('file_path', 255)->nullable();
            $table->timestamp('received_at')->nullable()->useCurrent();
            $table->foreignId('validated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('validated_at')->nullable();
            $table->timestamps();
            $table->unique(['supplier_id', 'series', 'number'], 'uq_invoice_supplier_number');
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('invoices'); }
};
