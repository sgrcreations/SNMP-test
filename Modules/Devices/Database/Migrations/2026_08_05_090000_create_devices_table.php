<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('devices', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('vendor', 50);
            $table->string('model')->nullable();
            $table->string('hostname')->nullable();
            $table->string('ip_address', 45);
            $table->string('snmp_version', 10)->default('v2c');
            $table->text('community')->nullable();
            $table->string('username')->nullable();
            $table->string('auth_protocol', 20)->nullable();
            $table->text('auth_password')->nullable();
            $table->string('priv_protocol', 20)->nullable();
            $table->text('priv_password')->nullable();
            $table->unsignedSmallInteger('port')->default(161);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->unsignedInteger('polling_interval')->default(60);
            $table->string('status', 20)->default('active');
            $table->string('reachability', 20)->default('unknown');
            $table->timestamp('last_polled_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['ip_address', 'port']);
            $table->index(['vendor', 'status']);
            $table->index('reachability');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('devices');
    }
};
