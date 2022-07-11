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
        Schema::create('dislikes', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id', 'dislikes_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'dislikes_users_id_foreign');
            $table->uuidMorphs('dislikeable');
            $table->index(['dislikeable_id', 'dislikeable_type'], 'dislikeable');
//            $table->foreignUuid('post_id', 'dislikes_rum_posts_id_foreign')
//                ->references('id')->on('rum_posts')
//                ->onUpdate("restrict")
//                ->onDelete("restrict");
//            $table->index(["post_id"], 'dislikes_rum_posts_id_foreign');
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
        Schema::dropIfExists('dislikes');
    }
};
