<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_metric_rollups', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained('devices')->cascadeOnDelete();
            $table->string('period', 8); // 5m | 1h
            $table->timestamp('bucket_at');
            $table->float('cpu_avg')->nullable();
            $table->float('cpu_max')->nullable();
            $table->float('memory_avg')->nullable();
            $table->float('memory_max')->nullable();
            $table->float('temperature_avg')->nullable();
            $table->float('temperature_max')->nullable();
            $table->unsignedInteger('samples')->default(0);
            $table->timestamps();

            $table->unique(['device_id', 'period', 'bucket_at']);
            $table->index(['device_id', 'period', 'bucket_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_metric_rollups');
    }
};
