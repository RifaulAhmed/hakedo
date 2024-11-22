<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateClientsTable extends Migration {

	/**
	 * Run the migrations.
	 *
	 * @return void
	 */
	public function up()
	{
		Schema::create('clients', function(Blueprint $table)
		{
			$table->integer('id', true);
			$table->integer('user_id')->index('clients_user_id');
			$table->string('username', 191);
			$table->string('code', 192);
			$table->boolean('status')->default(1);
			$table->string('photo', 192)->nullable();
			$table->string('email', 192)->nullable();
			$table->string('country', 191)->nullable();
			$table->string('city', 191)->nullable();
			$table->string('phone', 191)->nullable();
			$table->string('address', 191)->nullable();
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
		Schema::drop('clients');
	}

}
