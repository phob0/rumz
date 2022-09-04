<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSubscriptionsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('subscriptions', function (Blueprint $table) {
            $table->id();
            $table->foreignUuid('user_id', 'subscriptions_users_id_foreign')
                ->references('id')->on('users')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["user_id"], 'subscriptions_users_id_foreign');
            $table->foreignUuid('rum_id', 'subscriptions_rums_id_foreign')
                ->references('id')->on('rums')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["rum_id"], 'subscriptions_rums_id_foreign');
            $table->decimal('amount', $precision = 8, $scale = 2);
            $table->boolean('is_paid')->default(0);
            $table->boolean('granted')->default(0);
            $table->timestamp('expire_at');
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
        Schema::dropIfExists('subscriptions');
    }
}
