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
        Schema::create('admins_rums', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id', 'users_rums_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'admins_rums_users_id_foreign');
            $table->foreignUuid('rum_id', 'users_rums_rums_id_foreign')
                ->references('id')->on('rums')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["rum_id"], 'admins_rums_rums_id_foreign');
            $table->boolean('granted')->default(0);
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
        Schema::dropIfExists('admins_rums');
    }
};
