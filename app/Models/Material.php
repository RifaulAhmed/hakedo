<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Material extends Model
{
    // Define the table name if it's not the plural of the model name
    protected $table = 'materials';

    // Specify which attributes are mass assignable
    protected $fillable = [
        'code',
        'material_type',
        'material_description',
        'min_stock',
        'rop',
        'max_stock',
    ];

    /**
     * Relationship: each material belongs to a material type.
     * Assuming 'material_type' is a string, but we relate to the `MaterialType` model by name.
     */
    public function materialType()
    {
        return $this->belongsTo(MaterialType::class, 'material_type', 'name');
    }
    }
