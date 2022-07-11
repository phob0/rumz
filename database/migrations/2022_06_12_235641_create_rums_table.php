<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRumsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('rums', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id', 'rums_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'rums_users_id_foreign');
            $table->string('title');
            $table->tinyText('description');
//            $table->string('image');
            $table->enum('type', ['free', 'paid', 'private', 'confidential']);
            $table->enum('privilege', ['me', 'all', 'members']);
            $table->softDeletes();
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
        Schema::dropIfExists('rums');
    }
}
