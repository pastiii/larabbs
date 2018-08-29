<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreatePostsCateTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('posts_cate', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('parent_id')->default(0)->comment('分类父ID');
            $table->integer('order')->default(0)->comment('排序');
            $table->string('title', '50')->comment('名称');
            $table->string('code', '50')->comment('标识');
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
        Schema::dropIfExists('posts_cate');
    }
}
