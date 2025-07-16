<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('customer_briefs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subuser_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->integer('delivery_time');
            $table->string('tags'); // comma-separated
            $table->decimal('price', 10, 2)->nullable();
            $table->boolean('request_quote')->default(false);
            $table->string('status')->default('active');
            $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subuser_id')->references('id')->on('subusers')->onDelete('set null');
        });
    }

    public function down()
    {
        Schema::dropIfExists('customer_briefs');
    }
}; 