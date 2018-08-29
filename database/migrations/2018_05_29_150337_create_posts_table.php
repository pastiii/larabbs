<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('cate_id')->default(0)->index()->comment('分类ID');
            $table->string('title', '255')->comment('标题');
            $table->string('subtitle', '255')->comment('副标题');
            $table->text('content')->comment('内容');
            $table->text('summary')->comment('简介');
            $table->string('code')->comment('标识');
            $table->integer('hits')->default(0)->comment('点击次数');
            $table->tinyInteger('status')->default(0)->index()->comment('状态:0,隐藏 2,显示');
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
        Schema::dropIfExists('posts');
    }
}
