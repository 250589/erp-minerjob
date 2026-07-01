<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('warehouses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('parent_warehouse_id')->nullable()->constrained('warehouses')->nullOnDelete();
            $table->foreignId('manager_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->string('name', 150);
            $table->enum('type', ['principal', 'subalmacen', 'transito'])->default('principal');
            $table->string('address', 255)->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
        });
    }
    public function down(): void { Schema::dropIfExists('warehouses'); }
};
