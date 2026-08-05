<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('device_interfaces', function (Blueprint $table) {
            $table->id();
            $table->foreignId('device_id')->constrained()->cascadeOnDelete();
            $table->unsignedInteger('if_index');
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('oper_status', 30)->default('unknown');
            $table->unsignedBigInteger('speed')->nullable();
            $table->string('rx_bytes', 40)->default('0');
            $table->string('tx_bytes', 40)->default('0');
            $table->unsignedBigInteger('errors')->default(0);
            $table->decimal('utilization', 8, 2)->nullable();
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamps();

            $table->unique(['device_id', 'if_index']);
            $table->index(['device_id', 'oper_status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('device_interfaces');
    }
};
