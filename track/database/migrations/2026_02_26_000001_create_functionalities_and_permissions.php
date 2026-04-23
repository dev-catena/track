<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('functionalities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug', 100)->unique()->comment('Identificador único (ex: dock.management)');
            $table->string('platform', 20)->default('web')->comment('web ou app');
            $table->integer('sort_order')->default(0);
            $table->timestamps();
        });

        Schema::create('profile_functionality', function (Blueprint $table) {
            $table->id();
            $table->foreignId('profile_id')->constrained()->onDelete('cascade');
            $table->foreignId('functionality_id')->constrained()->onDelete('cascade');
            $table->unique(['profile_id', 'functionality_id']);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('profile_functionality');
        Schema::dropIfExists('functionalities');
    }
};
