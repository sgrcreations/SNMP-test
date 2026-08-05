<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('interface_metrics', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->foreignId('device_interface_id')->constrained('device_interfaces')->cascadeOnDelete();
            $table->string('rx_bytes', 40)->nullable();
            $table->string('tx_bytes', 40)->nullable();
            $table->unsignedBigInteger('errors')->default(0);
            $table->decimal('utilization', 8, 2)->nullable();
            $table->timestamp('recorded_at');
            $table->timestamps();

            $table->index(['device_interface_id', 'recorded_at']);
            $table->index(['device_id', 'recorded_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('interface_metrics');
    }
};
