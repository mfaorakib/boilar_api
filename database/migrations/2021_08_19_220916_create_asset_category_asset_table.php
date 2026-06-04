<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAssetCategoryAssetTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('asset_category_asset', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('asset_id')->comment('Asset id');
            $table->unsignedBigInteger('asset_category_id')->comment('Asset category id');

            $table->unique(['asset_category_id', 'asset_id']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('asset_category_asset');
    }
}
