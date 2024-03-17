<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterTableBucketChangeDate extends Migration
{
    /**
     * Run the migrations.
     * php artisan migrate --path=/database/migrations/2024_03_13_063954_alter_table_bucket_change_date.php
     * proses menghapus tabel dan membuat tabel kembali, karena data di dlm tabel supaya terhapus juga
     *
     * @return void
     */
    public function up()
    {
        Schema::dropIfExists('bucket');
        Schema::create('bucket', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->string('week')->nullable();
            $table->string('day')->nullable();
            $table->timestamp('date')->nullable()->comment('ambil dari kolom schedule di run list');
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
        Schema::dropIfExists('bucket');
    }
}
