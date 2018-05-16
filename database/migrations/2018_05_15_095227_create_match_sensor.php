<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMatchSensor extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_sensor', function (Blueprint $table) {
            $table->increments('sensor_id');
            $table->integer('user_id')->comment('用户ID');
            $table->string('device_sn')->comment('设备编号');
            $table->integer('match_id')->comment('比赛场次ID')->default(0);
            $table->double("acc_x",10,2)->comment('x加速度')->nullable();
            $table->double('acc_y',10,2)->comment('y加速度')->nullable();
            $table->double('acc_z',10,2)->comment('z加速度')->nullable();

            $table->double("gyro_x",10,2)->comment('x角速度')->nullable();
            $table->double('gyro_y',10,2)->comment('x角速度')->nullable();
            $table->double('gyro_z',10,2)->comment('x角速度')->nullable();

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
        Schema::dropIfExists('match_sensor');
    }
}
