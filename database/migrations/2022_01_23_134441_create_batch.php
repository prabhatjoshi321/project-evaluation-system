<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBatch extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch', function (Blueprint $table) {
            $table->string('batch_id');
            $table->foreign('batch_id')->references('batch_id')->on('batch_list')->onDelete('cascade');
            $table->string('USN');
            $table->foreign('USN')->references('USN')->on('student')->onDelete('cascade');
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
        Schema::dropIfExists('batch');
    }
}
