<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('quote_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requirement_id')->constrained('requirements')->restrictOnDelete();
            $table->foreignId('created_by')->constrained('users')->restrictOnDelete();
            $table->string('code', 30)->unique();
            $table->date('sent_date')->nullable();
            $table->date('deadline_date')->nullable();
            $table->enum('status', ['abierta', 'cerrada', 'cancelada'])->default('abierta');
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('quote_requests'); }
};
