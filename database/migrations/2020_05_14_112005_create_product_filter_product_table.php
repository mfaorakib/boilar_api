<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductFilterProductTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_filter_product', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('product_id')->comment('product id');
            $table->unsignedBigInteger('filter_id')->comment('common filter id');

            $table->unique(['filter_id', 'product_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_filter_product');
    }
}
