<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateBucketTable extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=/database/migrations/2024_02_28_215919_create_bucket_table.php
     *
     * @return void
     */
    public function up()
    {
        Schema::create('buckets', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('week')->nullable();
            $table->string('day')->nullable();
            $table->string('date')->nullable()->comment('ambil dari kolom schedule di run list');
            $table->text('run_list')->nullable();
            $table->string('value')->nullable();
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
        Schema::dropIfExists('buckets');
    }
}
