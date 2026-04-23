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
            $table->enum('operation', ['indoor', 'outdoor'])->default('indoor');
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->enum('operation', ['indoor', 'outdoor'])->default('indoor');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('operation');
        });

        Schema::table('operators', function (Blueprint $table) {
            $table->dropColumn('operation');
        });
    }
};
