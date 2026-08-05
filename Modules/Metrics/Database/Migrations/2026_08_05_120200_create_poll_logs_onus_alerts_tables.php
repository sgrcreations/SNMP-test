<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('poll_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->boolean('success')->default(false);
            $table->unsignedInteger('duration_ms')->default(0);
            $table->unsignedInteger('interfaces_count')->default(0);
            $table->text('message')->nullable();
            $table->json('meta')->nullable();
            $table->timestamp('started_at');
            $table->timestamp('finished_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'started_at']);
            $table->index('success');
        });

        Schema::create('device_onus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_interface_id')->nullable()->constrained('device_interfaces')->nullOnDelete();
            $table->string('serial')->nullable();
            $table->string('description')->nullable();
            $table->string('pon_port')->nullable();
            $table->unsignedInteger('onu_id')->nullable();
            $table->string('status', 30)->default('unknown');
            $table->decimal('rx_power_dbm', 10, 2)->nullable();
            $table->decimal('tx_power_dbm', 10, 2)->nullable();
            $table->unsignedInteger('distance_m')->nullable();
            $table->decimal('temperature', 10, 2)->nullable();
            $table->string('customer')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->index(['device_id', 'status']);
            $table->index(['device_id', 'pon_port']);
        });

        Schema::create('alerts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('device_interface_id')->nullable()->constrained('device_interfaces')->nullOnDelete();
            $table->string('type', 50);
            $table->string('severity', 20)->default('warning');
            $table->string('status', 20)->default('open');
            $table->string('title');
            $table->text('message')->nullable();
            $table->timestamp('raised_at');
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'raised_at']);
            $table->index(['device_id', 'type', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alerts');
        Schema::dropIfExists('device_onus');
        Schema::dropIfExists('poll_logs');
    }
};
