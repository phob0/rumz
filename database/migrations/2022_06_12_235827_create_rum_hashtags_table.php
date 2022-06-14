<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRumHashtagsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rum_hashtags', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('rum_id', 'rum_hashtags_rums_id_foreign')
                ->references('id')->on('rums')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["rum_id"], 'rum_hashtags_rums_id_foreign');
            $table->string('hashtag');
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
        Schema::dropIfExists('rum_hashtags');
    }
}
