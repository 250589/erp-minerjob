<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('area_id')->nullable()->after('id')
                ->constrained('areas')->nullOnDelete();
            $table->string('phone', 30)->nullable()->after('email');
            $table->enum('status', ['activo', 'inactivo'])->default('activo')->after('phone');
            $table->softDeletes();
        });
    }
    public function down(): void {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('area_id');
            $table->dropColumn(['phone', 'status', 'deleted_at']);
        });
    }
};
