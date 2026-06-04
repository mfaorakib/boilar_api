<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->constrained()->cascadeOnDelete();
            // $table->unsignedBigInteger('branch_id')->nullable()->comment('order branch name');
            $table->string('order_no')->nullable()->comment('order number');
            $table->string('invoice_no')->nullable()->comment('invoice number');
            $table->double('total_amount', 20, 2)->default(0)->comment('order total amount');
            $table->double('total_save_amount', 20, 2)->default(0)->comment('total save amount the order');
            $table->double('special_discount', 20, 2)->default(0)->comment('order special discount');
            $table->double('coupon_discount', 20, 2)->default(0)->comment('order coupon discount');
            $table->double('shipping_cost', 20, 2)->default(0)->comment('order shipping cost');
            $table->integer('total_quantity')->nullable()->comment('order total quantity');
            $table->text('comment')->nullable()->comment('comment by customer');
            $table->enum('platform', ['web', 'android', 'ios', 'manual'])->nullable()->comment('ordered platform');
            $table->json('static_address')->nullable()->comment('customer static address');
            $table->string('order_status')->nullable()->comment('order status code');
            $table->string('payment_method')->nullable()->comment('payment method code');
            $table->string('payment_status')->nullable()->comment('payment status code');
            $table->string('shipping_method')->nullable()->comment('shipping method code');
            $table->string('coupon')->nullable()->comment('coupon code');
            $table->string('delivery_mobile')->nullable();
            $table->string('delivery_email')->nullable();
            $table->enum('send_as_gift', ['yes', 'no'])->default('no')->comment('Oder send as gift');

            $table->softDeletes()->comment('soft delete datetime');
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
        Schema::dropIfExists('orders');
    }
}
