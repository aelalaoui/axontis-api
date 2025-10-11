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
        Schema::table('files', function (Blueprint $table) {
            // Supprimer l'index existant s'il existe
            $table->dropIndex(['fileable_type', 'fileable_id']);
        });

        Schema::table('files', function (Blueprint $table) {
            // Modifier fileable_id pour supporter les UUIDs (36 caractères pour UUID)
            $table->string('fileable_id', 36)->change();
        });

        Schema::table('files', function (Blueprint $table) {
            // Recréer l'index avec la nouvelle structure
            $table->index(['fileable_type', 'fileable_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('files', function (Blueprint $table) {
            // Supprimer l'index
            $table->dropIndex(['fileable_type', 'fileable_id']);
        });

        Schema::table('files', function (Blueprint $table) {
            // Revenir au type bigint unsigned
            $table->unsignedBigInteger('fileable_id')->change();
        });

        Schema::table('files', function (Blueprint $table) {
            // Recréer l'index original
            $table->index(['fileable_type', 'fileable_id']);
        });
    }
};
