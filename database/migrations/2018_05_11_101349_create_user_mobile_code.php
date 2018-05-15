<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateUserMobileCode extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('user_mobile_code', function (Blueprint $table) {
            $table->increments('id');
            $table->tinyInteger('status')->default(1)->comment('状态:既是否被使用过');
            $table->string('mobile')->comment('手机号码');
            $table->string('code')->comment('验证码');
            $table->string('msg_id')->nullable()->comment('短信记录ID');
            $table->string('data')->nullable()->comment('其他消息');
            $table->bigInteger('end_time')->comment('失效时间');

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
        Schema::dropIfExists('user_mobile_code');
    }
}
