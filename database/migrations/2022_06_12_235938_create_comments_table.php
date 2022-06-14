<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCommentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comments', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id', 'comments_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'comments_users_id_foreign');
            $table->foreignUuid('post_id', 'comments_rum_posts_id_foreign')
                ->references('id')->on('rum_posts')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["post_id"], 'comments_rum_posts_id_foreign');
            $table->tinyText('comment');
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
        Schema::dropIfExists('comments');
    }
}
