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
        Schema::create('device_location_logs', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('device_id')->nullable();
            $table->foreign('device_id')->references('id')->on('devices')->onDelete('set null');

            // Location data
            $table->decimal('latitude', 10, 7);   // e.g., 22.5726
            $table->decimal('longitude', 10, 7);  // e.g., 88.3639
            $table->timestamp('logged_at')->useCurrent(); // when location was captured

            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('device_location_logs');
    }
};
