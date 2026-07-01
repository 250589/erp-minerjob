<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quote_comparisons', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quote_request_id')->constrained('quote_requests')->cascadeOnDelete();
            $table->foreignId('selected_quote_id')->nullable()->constrained('quotes')->nullOnDelete();
            $table->foreignId('generated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('comparison_data')->nullable();
            $table->timestamp('generated_at')->nullable()->useCurrent();
            $table->unique('quote_request_id');
        });
    }
    public function down(): void { Schema::dropIfExists('quote_comparisons'); }
};
