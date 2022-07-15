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
        Schema::create('favourites', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id', 'favourites_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'favourites_users_id_foreign');
            $table->foreignUuid('post_id', 'favourites_rum_posts_id_foreign')
                ->references('id')->on('rum_posts')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["post_id"], 'favourites_rum_posts_id_foreign');
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
        Schema::dropIfExists('favourites');
    }
};
