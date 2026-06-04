<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateAreasTable extends Migration
{
    public function up()
    {
        Schema::create('areas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('district_id')->comment('district id')->constrained()->cascadeOnDelete();
            $table->enum('status', ['active', 'inactive'])->default('active')->comment('check, if active or inactive');
            $table->string('name')->comment('area name');
            $table->string('bn_name')->comment('area bengali name');
        });
    }

    public function down()
    {
        Schema::dropIfExists('areas');
    }
}
