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
        Schema::create('properties', function (Blueprint $table) {
            $table->id();

            // Colonnes polymorphes basées sur UUID
            $table->string('extendable_type', 100)->index();
            $table->string('extendable_id', 36)->index(); // UUID

            // Colonnes de propriétés avec index
            $table->string('property', 100)->index();
            $table->text('value')->nullable();
            $table->string('type', 50)->default('string')->index();

            $table->timestamps();

            // Index composé pour optimiser les requêtes polymorphes
            $table->index(['extendable_type', 'extendable_id', 'property'], 'properties_lookup');
            $table->index(['property', 'type'], 'properties_property_type');

            // Index unique pour éviter les doublons de propriétés
            $table->unique(['extendable_type', 'extendable_id', 'property'], 'properties_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('properties');
    }
};
