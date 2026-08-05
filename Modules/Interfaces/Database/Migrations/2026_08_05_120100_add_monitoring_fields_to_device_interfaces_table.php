<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('device_interfaces', function (Blueprint $table) {
            $table->boolean('is_uplink')->default(false)->after('utilization');
            $table->string('port_role', 30)->default('access')->after('is_uplink'); // access|uplink|pon
            $table->string('rx_bps', 40)->nullable()->after('port_role');
            $table->string('tx_bps', 40)->nullable()->after('rx_bps');
            $table->decimal('rx_power_dbm', 8, 2)->nullable()->after('tx_bps');
            $table->decimal('tx_power_dbm', 8, 2)->nullable()->after('rx_power_dbm');
            $table->decimal('temperature', 8, 2)->nullable()->after('tx_power_dbm');
            $table->unsignedInteger('onu_online')->default(0)->after('temperature');
            $table->unsignedInteger('onu_total')->default(0)->after('onu_online');
            $table->index('port_role');
        });
    }

    public function down(): void
    {
        Schema::table('device_interfaces', function (Blueprint $table) {
            $table->dropIndex(['port_role']);
            $table->dropColumn([
                'is_uplink',
                'port_role',
                'rx_bps',
                'tx_bps',
                'rx_power_dbm',
                'tx_power_dbm',
                'temperature',
                'onu_online',
                'onu_total',
            ]);
        });
    }
};
