<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('suppliers', function (Blueprint $table) {
            $table->id();
            $table->string('business_name', 180);
            $table->string('trade_name', 180)->nullable();
            $table->string('tax_id', 20)->unique();
            $table->string('address', 255)->nullable();
            $table->string('phone', 30)->nullable();
            $table->string('email', 150)->nullable();
            $table->char('currency_default', 3)->default('PEN');
            $table->integer('payment_term_days')->default(0);
            $table->string('bank_name', 100)->nullable();
            $table->string('bank_account', 40)->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('suppliers'); }
};
