<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pending_devices', function (Blueprint $table) {
            $table->id();
            $table->string('mac_address', 17)->unique();
            $table->string('device_name');
            $table->string('ip_address', 45)->nullable();
            $table->string('wifi_ssid')->nullable();
            $table->enum('status', ['pending', 'activated', 'rejected'])->default('pending');
            $table->bigInteger('registered_at');
            $table->json('device_info')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->unsignedBigInteger('mqtt_topic_id')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at']);
            $table->index('mac_address');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pending_devices');
    }
};
