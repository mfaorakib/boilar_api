<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShippingProviderTranslationsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipping_provider_translations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipping_provider_id')->comment('shipping provider id')->constrained()->cascadeOnDelete();
            $table->string('locale')->index()->comment('language');
            $table->string('name')->nullable()->comment('shipping provider name');
            $table->text('description')->nullable()->comment('shipping provider description');

            $table->unique(['shipping_provider_id', 'locale'], 'shipping_provider_id_locale_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipping_provider_translations');
    }
}
