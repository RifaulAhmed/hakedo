<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CountableUnloadingForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'date', 
        'do_number', 
        'license_plate_number', 
        'dn_number',
        'start', 
        'finish', 
        'product_description', 
        'production_lot', 
        'qty_do', 
        'ts_1', 
        'ts_2', 
        'ts_3', 
        'ts_4', 
        'ts_5', 
        'ts_6', 
        'ts_7', 
        'ts_8', 
        'ts_9', 
        'ts_10', 
        'total',
        'batch_number',
        'product_id',
        'supplier_id',
        'material_id'
    ];
    
    
    
    public function provider()
    {
        return $this->belongsTo(Provider::class, 'supplier_id');  
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');  
    }
    public function material()
    {
        return $this->belongsTo(Material::class,'material_id');  
    }
    
    
    
    
}
