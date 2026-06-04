<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBranchesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('branches', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable()->comment("Branch name");
            $table->string('email')->nullable()->comment("Branch email");
            $table->string('mobile')->nullable()->comment("Branch contact number");
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('check, if active or inactive');
            $table->string('type')->nullable()->comment("Bracnh type");;
            $table->longText('address')->nullable()->comment("Branch location or address");
            $table->json('open_days')->nullable()->comment('Branch opening days');
            $table->dateTime('open_time')->nullable()->comment('Branch opening time');
            $table->dateTime('close_time')->nullable()->comment('Branch closing time');
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
        Schema::dropIfExists('branches');
    }
}
