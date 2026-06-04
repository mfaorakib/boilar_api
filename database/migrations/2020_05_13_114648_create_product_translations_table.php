<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProductTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('product_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index();

            $table->string('name')->nullable()->comment('product name');
            $table->string('slug')->nullable()->comment('product slug');
            $table->longText('excerpt')->nullable()->comment('product excerpt');

            $table->text('warranty_note')->nullable()->comment('product warranty note');
            $table->text('delivery_note')->nullable()->comment('product delivery note');
            $table->text('payment_note')->nullable()->comment('product payment note');


            $table->string('meta_title')->nullable()->comment('product seo meta title');
            $table->longText('meta_description')->nullable()->comment('product seo meta description');
            $table->longText('meta_keyword')->nullable()->comment('product seo meta keyword');

            $table->unique(['product_id', 'locale']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('product_translations');
    }
}
