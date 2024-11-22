<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosSettingsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('pos_settings', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->string('note_customer', 192)->default('Thank You For Shopping With Us . Please Come Again');
			$table->boolean('show_note')->default(1);
			$table->boolean('show_barcode')->default(1);
			$table->boolean('show_discount')->default(1);
			$table->boolean('show_customer')->default(1);
			$table->boolean('show_email')->default(1);
			$table->boolean('show_phone')->default(1);
			$table->boolean('show_address')->default(1);
			$table->boolean('show_Warehouse')->default(1);
			$table->boolean('is_printable')->default(1);
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
		Schema::drop('pos_settings');
	}

}
