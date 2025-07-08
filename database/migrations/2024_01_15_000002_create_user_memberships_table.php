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
        Schema::create('user_memberships', function (Blueprint $table) {
            $table->id();
            $table->decimal('package_price', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('currency');
            $table->string('currency_symbol');
            $table->string('payment_method');
            $table->string('transaction_id');
            $table->enum('status', [0, 1, 2])->default(0); // 0=pending, 1=active, 2=expired
            $table->boolean('is_trial')->default(false);
            $table->integer('trial_days')->default(0);
            $table->string('receipt')->nullable();
            $table->text('transaction_details')->nullable();
            $table->text('settings')->nullable();
            $table->unsignedBigInteger('package_id');
            $table->unsignedBigInteger('user_id');
            $table->date('start_date');
            $table->date('expire_date');
            $table->string('conversation_id')->nullable();
            $table->timestamps();

            $table->foreign('package_id')->references('id')->on('user_packages')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('user_memberships');
    }
}; 