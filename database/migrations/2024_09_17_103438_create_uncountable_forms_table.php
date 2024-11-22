<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUncountableUnloadingFormsTable extends Migration
{
    public function up()
    {
        Schema::create('uncountable_unloading_forms', function (Blueprint $table) {
            $table->id();
            $table->date('date');
            $table->string('dn_number');  // Delivery Note number
            $table->string('product_name');
            $table->string('supplier_name');
            $table->decimal('standard_weight', 10, 2);  // Standard weight of product
            $table->integer('no_of_boxes');
            $table->decimal('total_weight', 10, 2);  // Total weight of all boxes
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('uncountable_unloading_forms');
    }
}