<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCustomerDevicesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('customer_devices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->cascadeOnDelete()->comment('Device of customer');
            $table->string('token')->comment('FCM device token');
            $table->string('version')->nullable()->comment('Device version');
            $table->string('model')->nullable()->comment('Device model');
            $table->enum('platform', ['web', 'android', 'ios'])->nullable()->comment('Device platform');
            $table->timestamps();
            $table->unique(['token']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('customer_devices');
    }
}
