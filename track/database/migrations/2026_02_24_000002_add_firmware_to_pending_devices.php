<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('pending_devices', function (Blueprint $table) {
            $table->string('firmware_version', 50)->nullable()->after('device_info');
            $table->timestamp('firmware_updated_at')->nullable()->after('firmware_version');
            $table->timestamp('last_seen_at')->nullable()->after('firmware_updated_at');
        });
    }

    public function down(): void
    {
        Schema::table('pending_devices', function (Blueprint $table) {
            $table->dropColumn(['firmware_version', 'firmware_updated_at', 'last_seen_at']);
        });
    }
};
