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
        Schema::table('docks', function (Blueprint $table) {
            $table->enum('dock_status', ['available', 'inuse'])->default('available');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dock_status', function (Blueprint $table) {
           $table->dropColumn('dock_status');
        });
    }
};
