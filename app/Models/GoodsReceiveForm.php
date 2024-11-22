<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GoodsReceiveForm extends Model
{
    use HasFactory;
 
    protected $table = 'goods_receive_forms';
 
    protected $fillable = [
        'no_form',
        'machine_no',
        'date',
        'shift',
        'operator_name',
        'product_id',
        'mo_no',
        'product_description',
        'total',
    ];

    // Optional: Define any relationships if applicable (e.g. Product, Operator)
    // public function product()
    // {
    //     return $this->belongsTo(Product::class, 'product_id');
    // }

    // public function operator()
    // {
    //     return $this->belongsTo(User::class, 'operator_name');
    // }
}
