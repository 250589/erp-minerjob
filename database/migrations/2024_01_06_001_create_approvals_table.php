<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('approvals', function (Blueprint $table) {
            $table->id();
            $table->string('approvable_type', 60);
            $table->unsignedBigInteger('approvable_id');
            $table->foreignId('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approver_id')->nullable()->constrained('users')->nullOnDelete();
            $table->enum('status', ['pendiente', 'aprobado', 'rechazado'])->default('pendiente');
            $table->string('comments', 500)->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamp('created_at')->nullable()->useCurrent();
            $table->index(['approvable_type', 'approvable_id'], 'idx_approvals_morph');
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('approvals'); }
};
