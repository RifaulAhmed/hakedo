<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPaymentPurchaseReturnsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_purchase_returns', function(Blueprint $table)
		{
			$table->foreign('payment_method_id', 'payment_method_id_payment_purchase_returns')->references('id')->on('payment_methods')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_id', 'payment_purchase_returns_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('purchase_return_id', 'supplier_id_payment_return_purchase')->references('id')->on('purchase_returns')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'user_id_payment_return_purchase')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_purchase_returns', function(Blueprint $table)
		{
			$table->dropForeign('payment_method_id_payment_purchase_returns');
			$table->dropForeign('payment_purchase_returns_account_id');
			$table->dropForeign('supplier_id_payment_return_purchase');
			$table->dropForeign('user_id_payment_return_purchase');
		});
	}

}
