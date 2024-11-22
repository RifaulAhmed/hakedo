<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
// use Illuminate\Database\Eloquent\SoftDeletes;

class ReturnMaterialForm extends Model
{
    use HasFactory;

    protected $table = 'return_material_forms';

    protected $fillable = [
        'id',
        'Ref',
        'date',
        'shift',
        'operator_name',
        'mo_no',
        'material_id',
        'material_description',
        'quantity',
        'uom',
        'remarks',
    ];

    // protected $dates = ['deleted_at'];
}
