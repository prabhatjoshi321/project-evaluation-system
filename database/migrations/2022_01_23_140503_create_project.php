<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProject extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project', function (Blueprint $table) {
            $table->bigIncrements('pid');
            $table->string('project_name');
            $table->string('batch_id');
            $table->foreign('batch_id')->references('batch_id')->on('batch_list')->onDelete('cascade');
            $table->string('S_ID');
            $table->foreign('S_ID')->references('S_ID')->on('staff')->onDelete('cascade');
            $table->string('file_link')->default('');
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
        Schema::dropIfExists('project');
    }
}
