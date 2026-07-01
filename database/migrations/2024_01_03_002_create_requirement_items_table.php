<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('requirement_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained('requirements')->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('description', 255);
            $table->decimal('quantity', 14, 4);
            $table->decimal('estimated_unit_price', 14, 4)->nullable();
        });
    }
    public function down(): void { Schema::dropIfExists('requirement_items'); }
};
