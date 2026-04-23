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
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id')->nullable();
            $table->unsignedBigInteger('operator_id')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();

            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');
            $table->foreign('operator_id')->references('id')->on('operators')->onDelete('set null');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
            $table->enum('status', ['pending', 'resolved'])->default('pending')->nullable();

            $table->string('type');      // e.g., overdue_device, lost_device
            $table->string('title');
            $table->text('description');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
