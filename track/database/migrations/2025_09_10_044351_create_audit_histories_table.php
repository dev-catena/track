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
        Schema::create('audit_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('device_id')->nullable();

            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('set null');
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');

            $table->enum('audit_type', ['check_in', 'check_out'])->nullable()->default('check_in');
            $table->string('audit_lat')->nullable();
            $table->string('audit_long')->nullable();
            $table->string('audit_in_time',50)->nullable();
            $table->string('audit_out_time',50)->nullable();
            $table->date('audit_date')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
            $table->foreign('updated_by')->references('id')->on('users')->onDelete('set null');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('audit_histories');
    }
};
