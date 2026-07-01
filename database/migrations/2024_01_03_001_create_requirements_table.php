<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('requirements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('requester_id')->constrained('users')->restrictOnDelete();
            $table->foreignId('area_id')->nullable()->constrained('areas')->nullOnDelete();
            $table->string('code', 30)->unique();
            $table->text('justification')->nullable();
            $table->date('required_date')->nullable();
            $table->enum('status', ['pendiente','en_cotizacion','aprobado','rechazado','convertido_oc','completado'])->default('pendiente');
            $table->timestamps();
            $table->index('status');
        });
    }
    public function down(): void { Schema::dropIfExists('requirements'); }
};
