<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone')->nullable();
            $table->enum('role', ['superadmin', 'admin', 'manager','supervisor','user'])->default('user');
            $table->string('plain_password')->nullable();
            $table->longText('address')->nullable();
            $table->enum('shift_type', ['morning', 'evening'])->default('morning')->nullable();
            $table->enum('status', ['active', 'inactive'])->default('active')->nullable();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['role', 'plain_password', 'deleted_at']);
        });
    }
};
