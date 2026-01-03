<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seeder_logs', function (Blueprint $table) {
            $table->id();
            $table->string('seeder_name')->unique();
            $table->timestamp('executed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seeder_logs');
    }
};
