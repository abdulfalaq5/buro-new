<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTempPricesTable extends Migration
{
    /**
     * Run the migrations.
     *  php artisan migrate --path=/database/migrations/2024_03_16_221217_create_temp_prices_table.php
     *
     * @return void
     */
    public function up()
    {
        Schema::create('temp_prices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('order_id')->nullable();
            $table->string('price')->nullable();
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
        Schema::dropIfExists('temp_prices');
    }
}
