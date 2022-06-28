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
        Schema::create('history_payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subscription_id', 'history_payments_subscription_id_foreign')
                ->references('id')->on('subscriptions')
                ->onUpdate("restrict")
                ->onDelete("restrict");
            $table->index(["subscription_id"], 'history_payments_subscription_id_foreign');
            $table->decimal('amount', 8, 2)->default(1);
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
        Schema::dropIfExists('history_payments');
    }
};
