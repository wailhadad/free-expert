<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up()
    {
        DB::statement("ALTER TABLE customer_offers MODIFY COLUMN status ENUM('pending', 'checkout_pending', 'accepted', 'declined', 'expired') DEFAULT 'pending'");
    }

    public function down()
    {
        DB::statement("ALTER TABLE customer_offers MODIFY COLUMN status ENUM('pending', 'accepted', 'declined', 'expired') DEFAULT 'pending'");
    }
}; 