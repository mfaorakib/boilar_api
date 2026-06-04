<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->boolean('status')->default(0)->comment('publish status');
            $table->enum('approved_status', ['pending', 'approved', 'denied'])->default('pending')->comment('approved by admin');
            $table->text('image')->nullable()->comment('product image');
            $table->text('meta_image')->nullable()->comment('product seo meta image');
            $table->boolean('is_featured')->default(0)->comment('featured product');
            $table->string('sku')->nullable()->comment('product sku');

            $table->decimal('weight', 20, 2)->unsigned()->default(0)->comment('product weight');
            $table->decimal('width', 20, 2)->unsigned()->default(0)->comment('product width');
            $table->decimal('height', 20, 2)->unsigned()->default(0)->comment('product height');
            $table->unsignedInteger('quantity')->default(0)->comment('product quantity');
            $table->unsignedBigInteger('sales_count')->default(0)->comment('product sales count');
            $table->unsignedInteger('min')->default(1)->comment('product minimum order');
            $table->unsignedInteger('max')->default(0)->comment('product maximum order');

            $table->decimal('purchased_price', 20, 2)->unsigned()->nullable()->comment('product purchased price');
            $table->decimal('price', 20, 2)->unsigned()->nullable()->comment('product price');

            $table->decimal('special_price', 20, 2)->unsigned()->nullable()->comment('product special price');
            $table->dateTime('special_start_date')->nullable()->comment('special campaign start date of product');
            $table->dateTime('special_end_date')->nullable()->comment('special campaign end date of product');
            $table->text('video_url')->nullable()->comment('product video url');

            $table->dateTime('datetime')->nullable()->comment('publish date and time of product');
            $table->softDeletes();

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
        Schema::dropIfExists('products');
    }
}
