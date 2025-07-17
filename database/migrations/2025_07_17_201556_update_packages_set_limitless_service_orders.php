<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Update all existing packages to have limitless service orders
        DB::table('packages')->update(['number_of_service_order' => -1]);
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        // Revert to a default value (e.g., 100) if needed
        DB::table('packages')->update(['number_of_service_order' => 100]);
    }
};
