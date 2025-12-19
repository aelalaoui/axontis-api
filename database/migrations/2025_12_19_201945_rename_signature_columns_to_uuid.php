<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn(['signable_id', 'signable_by_id']);
            $table->string('signable_uuid')->after('signable_type');
            $table->string('signable_by_uuid')->nullable()->after('signable_by_type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('signatures', function (Blueprint $table) {
            $table->dropColumn(['signable_uuid', 'signable_by_uuid']);
            $table->unsignedBigInteger('signable_id')->after('signable_type');
            $table->unsignedBigInteger('signable_by_id')->nullable()->after('signable_by_type');
        });
    }
};
