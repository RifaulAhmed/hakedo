<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentSaleReturnsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payment_sale_returns', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('user_id')->index('factures_sale_return_user_id');
			$table->integer('account_id')->nullable()->index('payment_sale_returns_account_id');
			$table->dateTime('date');
			$table->string('Ref', 192);
			$table->integer('sale_return_id')->index('factures_sale_return');
			$table->float('montant', 10, 0);
			$table->float('change', 10, 0)->default(0);
			$table->integer('payment_method_id')->index('payment_method_id_payment_sale_returns');
			$table->text('notes')->nullable();
			$table->timestamps(6);
			$table->softDeletes();
		});
	}


	/**
	 * Reverse the migrations.
	 *
	 * @return void
	 */
	public function down()
	{
		Schema::drop('payment_sale_returns');
	}

}
