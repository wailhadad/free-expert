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
        Schema::create('direct_chat_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('chat_id');
            $table->unsignedBigInteger('sender_id');
            $table->enum('sender_type', ['user', 'seller']);
            $table->text('message');
            $table->string('file_name')->nullable();
            $table->string('file_original_name')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamps();
            $table->foreign('chat_id')->references('id')->on('direct_chats')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('direct_chat_messages');
    }
};
