<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('customer_offers', function (Blueprint $table) {
            $table->integer('delivery_time')->nullable()->after('expires_at');
            $table->timestamp('dead_line')->nullable()->after('delivery_time');
        });
    }

    public function down()
    {
        Schema::table('customer_offers', function (Blueprint $table) {
            $table->dropColumn(['delivery_time', 'dead_line']);
        });
    }
}; 