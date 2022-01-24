<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBatchList extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('batch_list', function (Blueprint $table) {
            $table->string('batch_id')->primary()->unique();
            $table->string('branch');
            $table->string('S_ID');
            $table->foreign('S_ID')->references('S_ID')->on('staff')->onDelete('cascade');
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
        Schema::dropIfExists('batch_list');
    }
}
