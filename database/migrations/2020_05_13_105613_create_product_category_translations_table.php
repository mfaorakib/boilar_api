<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductCategoryTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_category_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('category_id')->comment('product category id')->constrained('product_categories')->cascadeOnDelete();
            $table->string('locale')->index()->comment('language');
            $table->string('name')->nullable()->comment('product category name');
            $table->string('slug')->unique()->nullable()->comment('product category slug');
            $table->text('description')->nullable()->comment('product category description');
            $table->string('meta_title')->nullable()->comment('product category seo meta title');
            $table->text('meta_description')->nullable()->comment('product category seo meta description');
            $table->text('meta_keyword')->nullable()->comment('product category seo meta keyword');

            $table->unique(['category_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_category_translations');
    }
}
