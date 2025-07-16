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
        Schema::table('direct_chats', function (Blueprint $table) {
            $table->unsignedBigInteger('brief_id')->nullable()->after('subuser_id');
            $table->foreign('brief_id')->references('id')->on('customer_briefs')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('direct_chats', function (Blueprint $table) {
            $table->dropForeign(['brief_id']);
            $table->dropColumn('brief_id');
        });
    }
};
