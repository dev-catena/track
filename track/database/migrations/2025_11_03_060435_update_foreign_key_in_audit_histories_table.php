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
        Schema::table('audit_histories', function (Blueprint $table) {
            // Drop existing foreign keys
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            // Add new foreign keys referencing 'operators' table
            $table->foreign('created_by')->references('id')->on('operators')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('operators')->onDelete('set null');

        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('audit_histories', function (Blueprint $table) {
            // Rollback to users table reference
            $table->dropForeign(['created_by']);
            $table->dropForeign(['updated_by']);

            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
        });
    }
};
