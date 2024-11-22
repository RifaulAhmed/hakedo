<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MaterialRequestForm extends Model
{
    use HasFactory;

    protected $table = 'material_request_forms';

    protected $fillable = [
        'Ref', 'date', 'shift', 'operator_name', 'mo_no',
        'material_id', 'material_description', 'quantity', 'uom', 'remarks'
    ];


    // public function material()
    // {
    //     return $this->belongsTo(Material::class, 'material_id');
    // }
}
