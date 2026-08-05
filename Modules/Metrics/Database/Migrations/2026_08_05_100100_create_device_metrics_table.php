<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->decimal('cpu', 8, 2)->nullable();
            $table->decimal('memory', 8, 2)->nullable();
            $table->decimal('temperature', 8, 2)->nullable();
            $table->string('rx_bytes', 40)->nullable();
            $table->string('tx_bytes', 40)->nullable();
            $table->string('uptime')->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_metrics');
    }
};
