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
        Schema::create('user_packages', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->decimal('price', 10, 2);
            $table->enum('term', ['monthly', 'yearly', 'lifetime']);
            $table->boolean('is_trial')->default(false);
            $table->integer('trial_days')->nullable();
            $table->boolean('status')->default(true);
            $table->integer('max_subusers')->default(0);
            $table->text('custom_features')->nullable();
            $table->boolean('recommended')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_packages');
    }
}; 