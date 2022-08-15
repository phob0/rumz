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
        Schema::table('subscriptions', function (Blueprint $table) {
            $table->decimal('owner_amount', $precision = 8, $scale = 2)->after('amount');
            $table->decimal('profit', $precision = 8, $scale = 2)->after('amount');
            $table->string('transfer_id')->after('rum_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('subscriptions', function(Blueprint $table) {
            $table->dropColumn('owner_amount');
            $table->dropColumn('profit');
            $table->dropColumn('transfer_id');
        });
    }
};
