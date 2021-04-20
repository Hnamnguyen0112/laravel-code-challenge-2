<?php

use App\Models\Loan;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ModifyScheduledRepaymentsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            $table->integer('amount')->default(0);
            $table->integer('outstanding_amount')->default(0);
            $table->date('due_date');
            $table->string('status')->default(Loan::STATUS_DUE);
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
        Schema::table('scheduled_repayments', function (Blueprint $table) {
            $table->dropColumn('amount');
            $table->dropColumn('outstanding_amount');
            $table->dropColumn('due_date');
            $table->dropColumn('status');
            $table->dropColumn('currency_code');
        });
    }
}
