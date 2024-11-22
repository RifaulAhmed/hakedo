<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingMaterialQClabel extends Model
{
    use HasFactory;

    protected $fillable = [
        'material_id',
        'material_description',
        'supplier_id',
        'supplier_name',
        'delivery_note_number',
        'batch_number',
        'remarks',
    ];

    public function provider()
    {
        return $this->belongsTo(provider::class, 'supplier_id');
    }
}
