<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDistrictsTable extends Migration
{
    public function up()
    {
        Schema::create('districts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('division_id')->comment('division id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('check, if active or inactive');
            $table->string('name')->comment('district name');
            $table->string('bn_name')->comment('district name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('districts');
    }
}
