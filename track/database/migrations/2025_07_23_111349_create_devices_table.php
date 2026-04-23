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
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('model_name');
            $table->enum('tag_type', ['qr', 'rfid','ncf'])->default('qr')->nullable();
            $table->string('tag_id');
            $table->unsignedBigInteger('dock_id')->nullable();
            $table->enum('status', ['active', 'inactive','maintenance'])->default('active')->nullable();
            $table->enum('device_status', ['inuse', 'available','offline','overdue'])->default('available')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->unsignedBigInteger('updated_by')->nullable();
            $table->foreign('dock_id')->references('id')->on('docks')->onDelete('set null');
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
        Schema::dropIfExists('devices');
    }
};
