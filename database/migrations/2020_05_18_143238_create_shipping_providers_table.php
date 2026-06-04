<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingProvidersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_providers', function (Blueprint $table) {
            $table->id();
            $table->enum('status', ['active', 'inactive'])->default('inactive')->comment('check, if active or inactive');
            $table->boolean('is_featured')->default(0)->comment('featured provider');
            $table->boolean('has_api')->default(0)->comment('check, if has api');
            $table->string('code')->unique()->nullable()->comment('provider code');
            $table->json('package_option')->nullable()->comment('package option');
            $table->json('delivery_option')->nullable()->comment('delivery option');
            $table->nestedSet();

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
        Schema::dropIfExists('shipping_providers');
    }
}
