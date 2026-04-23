<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('docks', function (Blueprint $table) {
            $table->string('pairing_code', 8)->nullable()->unique()->after('mqtt_topic_id');
        });

        // Gerar códigos para docas existentes
        $docks = DB::table('docks')->whereNull('pairing_code')->get();
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        foreach ($docks as $dock) {
            do {
                $code = '';
                for ($i = 0; $i < 6; $i++) {
                    $code .= $chars[random_int(0, strlen($chars) - 1)];
                }
            } while (DB::table('docks')->where('pairing_code', $code)->exists());
            DB::table('docks')->where('id', $dock->id)->update(['pairing_code' => $code]);
        }
    }

    public function down(): void
    {
        Schema::table('docks', function (Blueprint $table) {
            $table->dropColumn('pairing_code');
        });
    }
};
