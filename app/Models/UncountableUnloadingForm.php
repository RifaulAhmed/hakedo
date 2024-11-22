<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UncountableUnloadingForm extends Model
{
    use HasFactory;

    protected $fillable = [
        'date',
        'dn_number',
        'product_name',
        'supplier_name',
        'standard_weight',
        'no_of_boxes',
        'weight',
        'name',
        'license_plate_number',
        'batch_number',
        'product_id', 
        'material_id',
        'supplier_id'  
    ];

    // public function provider()
    // {
    //     return $this->belongsTo(Provider::class, 'Ref');  
    // }
    
    // public function product()
    // {
    //     return $this->belongsTo(Product::class, 'Ref');  
    // }
    
     public function provider()
    {
        return $this->belongsTo(Provider::class, 'supplier_id');  
    }
    
    public function material()
    {
        return $this->belongsTo(Material::class,'material_id');  
    }
    
    public function product()
    {
        return $this->belongsTo(Product::class,'product_id');  
    }
    
    
    
    
    
}
