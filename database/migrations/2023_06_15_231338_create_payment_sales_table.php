<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePaymentSalesTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('payment_sales', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('user_id')->index('user_id_payments_sale');
			$table->integer('account_id')->nullable()->index('account_id_payment_sales');
			$table->dateTime('date')->nullable();
			$table->string('Ref', 192);
			$table->integer('sale_id')->index('payment_sale_id');
			$table->float('montant', 10, 0);
			$table->float('change', 10, 0)->default(0);
			$table->integer('payment_method_id')->index('payment_method_id_payment_sales');
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
		Schema::drop('payment_sales');
	}

}
