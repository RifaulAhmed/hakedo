<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForeignKeysToPaymentSalesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::table('payment_sales', function(Blueprint $table)
		{
			$table->foreign('account_id', 'account_id_payment_sales')->references('id')->on('accounts')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('payment_method_id', 'payment_method_id_payment_sales')->references('id')->on('payment_methods')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('sale_id', 'payment_sales_sale_id')->references('id')->on('sales')->onUpdate('RESTRICT')->onDelete('RESTRICT');
			$table->foreign('user_id', 'user_id_payment_sales')->references('id')->on('users')->onUpdate('RESTRICT')->onDelete('RESTRICT');
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::table('payment_sales', function(Blueprint $table)
		{
			$table->dropForeign('account_id_payment_sales');
			$table->dropForeign('payment_method_id_payment_sales');
			$table->dropForeign('payment_sales_sale_id');
			$table->dropForeign('user_id_payment_sales');
		});
	}

}
