<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateMainSliderTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('main_slider_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('main_slider_id')->comment('home slider id');
            $table->string('locale')->index()->comment('language');
            $table->string('title')->nullable()->comment('home slider title');
            $table->string('subtitle')->nullable()->comment('home slider subtitle');

            $table->unique(['main_slider_id', 'locale']);
            $table->foreign('main_slider_id')->references('id')->on('main_sliders')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('main_slider_translations');
    }
}
