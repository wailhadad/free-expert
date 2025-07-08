<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->string('invoice')->nullable()->after('conversation_id');
        });
    }

    public function down()
    {
        Schema::table('user_memberships', function (Blueprint $table) {
            $table->dropColumn('invoice');
        });
    }
}; 