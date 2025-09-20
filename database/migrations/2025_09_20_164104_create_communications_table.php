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
        Schema::create('communications', function (Blueprint $table) {
            $table->id();
            $table->morphs('communicable');
            $table->enum('channel', ['email', 'phone', 'sms', 'whatsapp', 'other']);
            $table->enum('direction', ['inbound', 'outbound']);
            $table->string('subject', 200)->nullable();
            $table->text('message')->nullable();
            $table->unsignedBigInteger('handled_by')->nullable();
            $table->timestamp('sent_at')->useCurrent();
            $table->timestamps();
            
            $table->foreign('handled_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('communications');
    }
};
