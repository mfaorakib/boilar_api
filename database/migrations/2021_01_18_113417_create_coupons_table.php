<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCouponsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('check, if active or inactive');
            $table->string('name')->nullable()->comment('Coupon name');
            $table->string('code')->nullable()->comment('Coupon code');
            $table->enum('type', ['percentage', 'fixed'])->default('percentage')->comment('coupon range type');
            $table->json('area')->nullable()->comment('Allowed area of coupon');
            $table->decimal('discount', 20, 2)->unsigned()->nullable()->comment('Coupon discount price');
            $table->json('platforms')->nullable()->comment('coupon platforms');
            $table->decimal('total_amount', 20, 2)->unsigned()->default('100')->nullable()->comment('Coupon total amount');
            $table->dateTime('start_date')->nullable()->comment('Start date of coupon');
            $table->dateTime('end_date')->nullable()->comment('End date of coupon');
            $table->unsignedInteger('uses_per_coupon')->default(0)->comment('Uses per coupon');
            $table->unsignedInteger('uses_per_customer')->default(0)->comment('Uses per customer');
            
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
        Schema::dropIfExists('coupons');
    }
}
