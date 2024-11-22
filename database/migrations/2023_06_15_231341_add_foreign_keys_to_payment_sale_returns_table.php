<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPaymentSaleReturnsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_sale_returns', function(Blueprint $table)
		{
			$table->foreign('payment_method_id', 'payment_method_id_payment_sale_returns')->references('id')->on('payment_methods')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('account_id', 'payment_sale_returns_account_id')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sale_return_id', 'payment_sale_returns_sale_return_id')->references('id')->on('sale_returns')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'payment_sale_returns_user_id')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_sale_returns', function(Blueprint $table)
		{
			$table->dropForeign('payment_method_id_payment_sale_returns');
			$table->dropForeign('payment_sale_returns_account_id');
			$table->dropForeign('payment_sale_returns_sale_return_id');
			$table->dropForeign('payment_sale_returns_user_id');
		});
	}

}
