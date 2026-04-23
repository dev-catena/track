<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dock_slot_status', function (Blueprint $table) {
            $table->id();
            $table->string('id_doca')->index();
            $table->timestamp('ultima_atualizacao')->nullable();
            $table->json('slots')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dock_slot_status');
    }
};
