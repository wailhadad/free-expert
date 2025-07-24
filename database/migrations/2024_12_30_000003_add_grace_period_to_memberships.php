<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Add grace period columns to user_memberships table
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->datetime('grace_period_until')->nullable()->after('expire_date');
            $table->boolean('in_grace_period')->default(0)->after('grace_period_until');
        });

        // Add grace period columns to memberships table
        Schema::table('memberships', function (Blueprint $table) {
            $table->datetime('grace_period_until')->nullable()->after('expire_date');
            $table->boolean('in_grace_period')->default(0)->after('grace_period_until');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropColumn(['grace_period_until', 'in_grace_period']);
        });

        Schema::table('memberships', function (Blueprint $table) {
            $table->dropColumn(['grace_period_until', 'in_grace_period']);
        });
    }
}; 