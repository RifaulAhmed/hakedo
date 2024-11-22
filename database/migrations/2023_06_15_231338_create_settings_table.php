<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('settings', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('currency_id')->nullable()->index('settings_currency_id');
			$table->integer('client_id')->nullable()->index('settings_client_id');
			$table->integer('warehouse_id')->nullable()->index('settings_warehouse_id');
			$table->string('email', 191);
			$table->string('app_name', 192);
			$table->string('CompanyName', 191);
			$table->string('CompanyPhone', 191);
			$table->string('CompanyAdress', 191);
			$table->string('logo', 191)->nullable();
			$table->string('invoice_footer', 192)->nullable();
			$table->string('footer', 192);
			$table->string('developed_by', 192)->nullable();
			$table->string('default_language', 192)->default('en');
			$table->string('default_sms_gateway', 192)->nullable();
			$table->string('symbol_placement', 192)->default('before');
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
		Schema::drop('settings');
	}

}
