<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('payment_obligation_id')->constrained('payment_obligations')->restrictOnDelete();
            $table->date('payment_date');
            $table->enum('method', ['transferencia', 'deposito', 'cheque'])->default('transferencia');
            $table->decimal('amount', 14, 2);
            $table->char('currency', 3)->default('PEN');
            $table->decimal('exchange_rate', 10, 4)->default(1);
            $table->string('origin_account', 40)->nullable();
            $table->string('destination_account', 40)->nullable();
            $table->string('reference_number', 60)->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['registrado', 'confirmado'])->default('registrado');
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->timestamp('created_at')->nullable()->useCurrent();
        });
    }
    public function down(): void { Schema::dropIfExists('payments'); }
};
