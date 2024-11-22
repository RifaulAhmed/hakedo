<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPaymentPurchasesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_purchases', function(Blueprint $table)
		{
			$table->foreign('payment_method_id', 'payment_method_id_payment_purchases')->references('id')->on('payment_methods')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_id', 'payment_purchases_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('purchase_id', 'payment_purchases_purchase_id')->references('id')->on('purchases')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'payment_purchases_user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_purchases', function(Blueprint $table)
		{
			$table->dropForeign('payment_method_id_payment_purchases');
			$table->dropForeign('payment_purchases_account_id');
			$table->dropForeign('payment_purchases_purchase_id');
			$table->dropForeign('payment_purchases_user_id');
		});
	}

}
