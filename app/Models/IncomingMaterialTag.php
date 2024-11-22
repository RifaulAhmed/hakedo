<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class IncomingMaterialTag extends Model
{
    use HasFactory;

    protected $table = 'incoming_material_tag';

    protected $fillable = [
        'date',
        'batch_number',
        'product_id',
        'product_description',
        'supplier_id',
        'supplier_name',
    ];

    // Relationships
    public function product()
    {
        return $this->belongsTo(Product::class);
    }

    public function provider()
    {
        return $this->belongsTo(Provider::class);
    }
}
