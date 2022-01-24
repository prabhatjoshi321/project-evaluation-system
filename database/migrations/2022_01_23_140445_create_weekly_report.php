<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWeeklyReport extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('weekly_report', function (Blueprint $table) {
            $table->string('batch_id');
            $table->foreign('batch_id')->references('batch_id')->on('batch_list')->onDelete('cascade');
            $table->string('day');
            $table->integer('week');
            $table->date('date');
            $table->longText('remarks');
            $table->longText('comments');
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
        Schema::dropIfExists('weekly_report');
    }
}
