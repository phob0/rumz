<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('comment_replies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id', 'comments_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'comments_users_id_foreign');
            $table->foreignUuid('comment_id', 'comment_replies_comment_id_foreign')
                ->references('id')->on('comments')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["comment_id"], 'comment_replies_comment_id_foreign');
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
        Schema::dropIfExists('comment_replies');
    }
};
