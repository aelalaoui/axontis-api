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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->morphs('taskable');
            $table->text('address');
            $table->enum('status', ['scheduled', 'in_progress', 'completed'])->default('scheduled');
            $table->enum('type', ['installation', 'sav', 'prospection'])->default('scheduled');
            $table->date('scheduled_date')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            
            $table->foreign('user_id')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('tasks');
    }
};
