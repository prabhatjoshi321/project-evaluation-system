<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateProjectEvaluation extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('project_evaluation', function (Blueprint $table) {
            $table->unsignedBigInteger('pid');
            $table->foreign('pid')->references('pid')->on('project')->onDelete('cascade');
            $table->string('USN');
            $table->foreign('USN')->references('USN')->on('student')->onDelete('cascade');
            $table->integer('marks');
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
        Schema::dropIfExists('project_evaluation');
    }
}
