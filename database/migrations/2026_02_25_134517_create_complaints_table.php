<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateComplaintsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('citizen_id');
            $table->foreign('citizen_id')->references('id')->on('citizens')->onDelete('restrict');

            $table->unsignedBigInteger('complaint_type_id');
            $table->foreign('complaint_type_id')->references('id')->on('complaint_types')->onDelete('restrict');

            $table->unsignedBigInteger('entity_id');
            $table->foreign('entity_id')->references('id')->on('entities')->onDelete('restrict');

            $table->string('title');
            $table->longText('desc');

            $table->unsignedBigInteger('directorate_id');
            $table->foreign('directorate_id')->references('id')->on('directorates')->onDelete('restrict');

            $table->unsignedBigInteger('neighborhood_id');
            $table->foreign('neighborhood_id')->references('id')->on('neighborhoods')->onDelete('restrict');

            $table->string('address');
            $table->string('lat');
            $table->string('lang');
            $table->longText('attchaments')->nullable();

            $table->unsignedBigInteger('speel_level_id');
            $table->foreign('speel_level_id')->references('id')->on('speel_levels')->onDelete('restrict');

            $table->unsignedBigInteger('complaint_status_id');
            $table->foreign('complaint_status_id')->references('id')->on('complaint_statuses')->onDelete('restrict');

            $table->unsignedBigInteger('type_id');
            $table->foreign('type_id')->references('id')->on('types')->onDelete('restrict');

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
        Schema::dropIfExists('complaints');
    }
}
