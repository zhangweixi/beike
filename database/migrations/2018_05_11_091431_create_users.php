<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUsers extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('users', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name')->nullable()->comment('用户真实姓名');
            $table->string("nick_name")->nullable()->comment('昵称');
            $table->string('wx_openid')->nullable()->comment('微信openid');
            $table->string('wx_unionid')->nullable()->comment('微信unionid');
            $table->string("wx_name")->nullable()->comment('微信名');
            $table->string("head_img")->nullable()->comment('微信头像');
            $table->string("mobile")->comment('手机号');
            $table->date('birthday')->comment('生日');
            $table->string('sex')->nullable()->comment('性别');
            $table->integer('height')->default(0)->comment('身高');
            $table->integer('weight')->default(0)->comemnt('体重');
            $table->string('role1')->nullable()->comment('场上角色1');
            $table->string("role2")->nullable()->comment('场上角色2');
            $table->enum('foot',['R','L'])->default('R')->comment('习惯用脚');
            $table->string('device_sn')->nullable()->comment('设备编号');
            $table->integer('credit')->default(0)->comment('信用度');

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
        Schema::dropIfExists('users');
    }
}
