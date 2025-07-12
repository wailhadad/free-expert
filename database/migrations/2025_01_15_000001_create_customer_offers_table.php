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
        Schema::create('customer_offers', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('seller_id');
            $table->unsignedBigInteger('user_id');
            $table->unsignedBigInteger('subuser_id')->nullable();
            $table->unsignedBigInteger('form_id')->nullable();
            $table->string('title');
            $table->text('description');
            $table->decimal('price', 10, 2);
            $table->string('currency_symbol', 10)->default('$');
            $table->enum('status', ['pending', 'accepted', 'declined', 'expired'])->default('pending');
            $table->timestamp('expires_at')->nullable();
            $table->json('form_data')->nullable(); // Store form field data
            $table->unsignedBigInteger('accepted_order_id')->nullable(); // Link to order if accepted
            $table->timestamps();

            $table->foreign('chat_id')->references('id')->on('direct_chats')->onDelete('cascade');
            $table->foreign('seller_id')->references('id')->on('sellers')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
            $table->foreign('subuser_id')->references('id')->on('subusers')->onDelete('set null');
            $table->foreign('form_id')->references('id')->on('forms')->onDelete('set null');
            $table->foreign('accepted_order_id')->references('id')->on('service_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_offers');
    }
}; 