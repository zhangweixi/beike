<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateGps extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_gps', function (Blueprint $table) {
            $table->increments('gps_id');
            $table->integer('user_id')->comment('用户ID');
            $table->string('device_sn')->comment('设备编号');
            $table->integer('match_id')->comment('比赛场次ID')->default(0);
            $table->double("latitude",15,10)->comment('维度')->nullable();
            $table->double('longitude',15,10)->comment('经度')->nullable();
            $table->double('speed')->comment('速度')->nullable();
            $table->double('direction')->comment('方向')->nullable();
            $table->double('status')->comment('数据状态');
            $table->integer('data_key')->comment('数据的索引值');
            $table->dateTime('data_time')->comment('数据时间')->nullable();
            $table->string('source_data')->comment('原始数据');
            $table->dateTime('created_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('match_gps');
    }
}
