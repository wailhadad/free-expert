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
        Schema::table('direct_chat_messages', function (Blueprint $table) {
            $table->string('file_name')->nullable()->after('message');
            $table->string('file_original_name')->nullable()->after('file_name');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('direct_chat_messages', function (Blueprint $table) {
            $table->dropColumn('file_name');
            $table->dropColumn('file_original_name');
        });
    }
};
