<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::create('signatures', function (Blueprint $table) {
            $table->id();
            $table->morphs('signable');
            $table->morphs('signable_by');
            $table->string('signature_file')->nullable();
            $table->enum('signature_type', ['digital', 'electronic', 'handwritten']);
            $table->timestamp('signed_at')->useCurrent();
            $table->string('ip_address')->nullable();
            $table->text('metadata')->nullable(); // JSON pour infos supplémentaires
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('signatures');
    }
};
