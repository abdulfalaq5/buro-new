<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRunListTable extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=/database/migrations/2024_02_27_212824_create_run_list_table.php
     *
     * @return void
     */
    public function up()
    {
        Schema::create('run_lists', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('bucket_id')->nullable();
            $table->string('week')->nullable();
            $table->string('number')->nullable();
            $table->date('schedule')->nullable()->comment('penjadwalan +1 dari hari order');
            $table->timestamps();
        });

        Schema::create('run_list_details', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('run_list_id')->nullable();
            $table->unsignedBigInteger('component_order_item')->nullable();
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
        Schema::dropIfExists('run_lists');
        Schema::dropIfExists('run_list_details');
    }
}
