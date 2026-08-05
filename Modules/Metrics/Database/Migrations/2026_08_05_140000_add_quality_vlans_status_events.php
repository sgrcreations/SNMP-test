<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_metrics', function (Blueprint $table) {
            $table->unsignedInteger('onu_online')->nullable()->after('uptime');
            $table->unsignedInteger('onu_total')->nullable()->after('onu_online');
        });

        Schema::create('ping_samples', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('latency_ms', 10, 2)->nullable();
            $table->decimal('jitter_ms', 10, 2)->nullable();
            $table->decimal('packet_loss_pct', 5, 2)->default(0);
            $table->unsignedTinyInteger('packets_sent')->default(0);
            $table->unsignedTinyInteger('packets_received')->default(0);
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });

        Schema::create('device_vlans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('vlan_id');
            $table->string('name')->nullable();
            $table->string('status', 30)->default('active');
            $table->unsignedInteger('member_ports')->default(0);
            $table->timestamps();

            $table->unique(['device_id', 'vlan_id']);
            $table->index(['device_id', 'status']);
        });

        Schema::create('device_status_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->string('category', 40); // poll|reachability|alarm|system
            $table->string('severity', 20)->default('info'); // info|warning|critical|success
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('occurred_at');
            $table->timestamps();

            $table->index(['device_id', 'occurred_at']);
            $table->index(['device_id', 'category']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_status_events');
        Schema::dropIfExists('device_vlans');
        Schema::dropIfExists('ping_samples');

        Schema::table('device_metrics', function (Blueprint $table) {
            $table->dropColumn(['onu_online', 'onu_total']);
        });
    }
};
