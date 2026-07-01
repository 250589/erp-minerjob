<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('chart_of_accounts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_id')->nullable()->constrained('chart_of_accounts')->nullOnDelete();
            $table->string('code', 20)->unique();
            $table->string('name', 150);
            $table->enum('type', ['activo','pasivo','patrimonio','ingreso','gasto']);
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->index('code');
        });
    }
    public function down(): void { Schema::dropIfExists('chart_of_accounts'); }
};
