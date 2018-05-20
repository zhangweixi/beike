<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class MatchSourceData extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('match_source_data', function (Blueprint $table) {

            $table->increments('match_source_id');
            $table->string('type')->comment('数据类型');
            $table->integer('user_id')->comment('用户ID');
            $table->integer('match_id')->comment('比赛场次ID');
            $table->string('device_sn')->comment('设备编号');
            $table->longText('data')->comment('gps数据');
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
        Schema::dropIfExists('match_source_data');
    }
}
