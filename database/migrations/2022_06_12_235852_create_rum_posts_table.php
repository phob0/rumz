<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRumPostsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rum_posts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('rum_id', 'rum_posts_rums_id_foreign')
                ->references('id')->on('rums')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["rum_id"], 'rum_posts_rums_id_foreign');
            $table->foreignUuid('user_id', 'rum_posts_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'rum_posts_users_id_foreign');
            $table->boolean('approved')->default(0);
            $table->string('description');
            $table->boolean('visible')->default(1);
            $table->json('metadata')->nullable();
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
        Schema::dropIfExists('rum_posts');
    }
}
