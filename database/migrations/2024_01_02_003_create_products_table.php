<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->nullable()->constrained('product_categories')->nullOnDelete();
            $table->foreignId('unit_id')->constrained('units_of_measure')->restrictOnDelete();
            $table->string('sku', 50)->unique();
            $table->string('name', 180);
            $table->text('description')->nullable();
            $table->decimal('min_stock', 14, 4)->default(0);
            $table->decimal('max_stock', 14, 4)->nullable();
            $table->decimal('markup_percentage', 6, 2)->default(35.00);
            $table->decimal('current_purchase_price', 14, 4)->nullable();
            $table->decimal('current_sale_price', 14, 4)->nullable();
            $table->enum('status', ['activo', 'inactivo'])->default('activo');
            $table->timestamps();
            $table->softDeletes();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('products'); }
};
