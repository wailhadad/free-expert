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
        Schema::table('customer_briefs', function (Blueprint $table) {
            $table->text('attachments')->nullable()->after('tags');
            $table->text('attachment_names')->nullable()->after('attachments');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('customer_briefs', function (Blueprint $table) {
            $table->dropColumn(['attachments', 'attachment_names']);
        });
    }
};
