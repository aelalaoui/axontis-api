<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alarm_events', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->uuid('device_uuid');
            $table->uuid('installation_uuid');
            $table->smallInteger('cid_code')->nullable()->index();
            $table->smallInteger('standard_cid_code')->nullable();
            $table->string('event_type', 50); // cidEvent, heartbeat, online, offline...
            $table->string('category', 20)->nullable(); // intrusion, fire, flood, panic, arming, system
            $table->string('severity', 10)->nullable(); // critical, high, medium, info
            $table->smallInteger('zone_number')->nullable();
            $table->string('zone_name', 100)->nullable();
            $table->timestampTz('triggered_at');
            $table->string('source_ip', 45)->nullable(); // IPv4 or IPv6
            $table->jsonb('raw_payload');
            $table->boolean('processed')->default(false);
            $table->timestampTz('processed_at')->nullable();
            $table->uuid('alert_uuid')->nullable();
            $table->timestamps();

            // Foreign keys
            $table->foreign('device_uuid')
                ->references('uuid')->on('devices')
                ->onDelete('cascade');

            $table->foreign('installation_uuid')
                ->references('uuid')->on('installations')
                ->onDelete('cascade');

            $table->foreign('alert_uuid')
                ->references('uuid')->on('alerts')
                ->onDelete('set null');

            // Multi-tenant queries
            $table->index(['installation_uuid', 'triggered_at'], 'idx_alarm_events_installation_triggered');

            // Lookup by device
            $table->index(['device_uuid', 'triggered_at'], 'idx_alarm_events_device_triggered');

            // Unprocessed jobs
            $table->index(['processed', 'created_at'], 'idx_alarm_events_unprocessed');

            // Severity filtering
            $table->index(['installation_uuid', 'severity', 'triggered_at'], 'idx_alarm_events_severity');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alarm_events');
    }
};

