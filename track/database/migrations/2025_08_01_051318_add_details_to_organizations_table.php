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
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('cnpj')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->foreignId('plan_id')->nullable()->constrained('plans')->nullOnDelete();
            $table->integer('max_devices')->nullable();
            $table->string('mdm')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->dropColumn(['cnpj', 'city', 'state', 'max_devices', 'mdm']);
            $table->dropForeign(['plan_id']);
            $table->dropColumn('plan_id');
        });
    }
};
