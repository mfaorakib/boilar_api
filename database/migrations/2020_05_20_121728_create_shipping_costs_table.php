<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingCostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_costs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('area_id')->comment('shipping area id')->constrained()->cascadeOnDelete();
            $table->double('cost', 20, 2)->nullable()->comment('shipping cost');
            $table->double('cart_amount', 20, 2)->nullable()->comment('shipping cost free of cart amount');
            $table->double('special_delivery_cost', 20, 2)->nullable()->comment('special delivery cost is when has free shipping cost offer');
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
        Schema::dropIfExists('shipping_costs');
    }
}
