<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyReceivedRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->integer('amount')->default(0);
            $table->date('received_at');
            $table->string('currency_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('received_repayments', function (Blueprint $table) {
            $table->dropColumn('amount')->default(0);
            $table->dropColumn('received_at');
            $table->dropColumn('currency_code');
        });
    }
}
