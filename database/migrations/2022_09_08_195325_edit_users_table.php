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
        Schema::table('users', function (Blueprint $table) {
            if (\DB::connection()->getDriverName() !== "sqlite") {
                $table->dropUnique('users_email_unique');
                $table->unique('phone');
            }
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('users', function (Blueprint $table) {
            if (\DB::connection()->getDriverName() !== "sqlite") {
                $table->dropUnique('users_phone_unique');
                $table->unique('email');
            }
        });
    }
};
