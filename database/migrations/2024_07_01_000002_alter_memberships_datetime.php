<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->datetime('start_date')->change();
            $table->datetime('expire_date')->change();
        });
    }

    public function down()
    {
        Schema::table('memberships', function (Blueprint $table) {
            $table->date('start_date')->change();
            $table->date('expire_date')->change();
        });
    }
}; 