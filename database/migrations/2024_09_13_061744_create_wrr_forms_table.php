<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWrrFormsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('wrr_forms', function (Blueprint $table) {
            $table->id();
            $table->string('wrr_no')->unique();
            $table->date('date'); 
            $table->string('dn_number'); 
            $table->string('po_number'); 
            $table->unsignedBigInteger('supplier_id'); 
            $table->string('supplier_name'); 
            $table->unsignedBigInteger('material_id');
            $table->string('material_description');
            $table->decimal('dn_quantity', 10, 2); 
            $table->decimal('received_quantity', 10, 2); 
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('wrr_forms');
    }
}
