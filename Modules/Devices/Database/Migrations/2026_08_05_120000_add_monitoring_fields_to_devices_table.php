<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->string('device_type', 30)->default('generic')->after('vendor');
            $table->string('area')->nullable()->after('location');
            $table->decimal('latitude', 10, 7)->nullable()->after('area');
            $table->decimal('longitude', 10, 7)->nullable()->after('latitude');
            $table->string('serial_number')->nullable()->after('model');
            $table->string('firmware')->nullable()->after('serial_number');
            $table->string('manufacturer')->nullable()->after('firmware');
            $table->unsignedInteger('interface_count')->default(0)->after('reachability');
            $table->decimal('last_cpu', 8, 2)->nullable()->after('interface_count');
            $table->decimal('last_memory', 8, 2)->nullable()->after('last_cpu');
            $table->decimal('last_temperature', 8, 2)->nullable()->after('last_memory');
            $table->string('sys_uptime')->nullable()->after('last_temperature');
            $table->index('device_type');
        });
    }

    public function down(): void
    {
        Schema::table('devices', function (Blueprint $table) {
            $table->dropIndex(['device_type']);
            $table->dropColumn([
                'device_type',
                'area',
                'latitude',
                'longitude',
                'serial_number',
                'firmware',
                'manufacturer',
                'interface_count',
                'last_cpu',
                'last_memory',
                'last_temperature',
                'sys_uptime',
            ]);
        });
    }
};
