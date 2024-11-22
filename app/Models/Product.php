<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\URL;


class Product extends Model
{
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'code', 'Ref', 'Type_barcode', 'name', 'cost', 'price', 'unit_id', 'unit_sale_id', 'unit_purchase_id',
        'stock_alert', 'category_id', 'sub_category_id', 'is_variant', 'is_imei', 'is_promo', 'promo_price', 
        'promo_start_date', 'promo_end_date', 'tax_method', 'image', 'brand_id', 'is_active', 'note', 
        'qty_min', 'warehouse_id', 'product_variant_id',
        // New fields
        'product_description', 'neck_type', 'volume', 'material', 'weight', 
        'colour', 'cycle_time', 'no_of_bottles', 'no_of_box', 'reference', 
        'waste', 'down_time', 'product_id_control', 'finished_goods', 'material_type', 'product_type'
    ];

    protected $casts = [
        'category_id' => 'integer',
        'sub_category_id' => 'integer',
        'unit_id' => 'integer',
        'unit_sale_id' => 'integer',
        'unit_purchase_id' => 'integer',
        'is_variant' => 'integer',
        'is_imei' => 'integer',
        'brand_id' => 'integer',
        'is_active' => 'integer',
        'cost' => 'double',
        'price' => 'double',
        'stock_alert' => 'double',
        'qty_min' => 'double',
        'TaxNet' => 'double',
        'is_promo' => 'integer',
        'promo_price' => 'double',
        'warehouse_id' => 'integer',
        'product_variant_id' => 'integer',
        // New casts
        'volume' => 'double', 
        'weight' => 'string', 
        'cycle_time' => 'string', 
        'no_of_bottles' => 'string', 
        'no_of_box' => 'string', 
        'waste' => 'string', 
        'down_time' => 'string', 
        'product_id_control' => 'string'
    ];

    public function variants()
    {
        return $this->hasMany(ProductVariant::class);
    }

    public function PurchaseDetail()
    {
        return $this->belongsTo('App\Models\PurchaseDetail');
    }

    public function SaleDetail()
    {
        return $this->belongsTo('App\Models\SaleDetail');
    }

    public function QuotationDetail()
    {
        return $this->belongsTo('App\Models\QuotationDetail');
    }

    public function category()
    {
        return $this->belongsTo('App\Models\Category');
    }

    public function unit()
    {
        return $this->belongsTo(Unit::class, 'unit_id');
    }

    public function unitPurchase()
    {
        return $this->belongsTo(Unit::class, 'unit_purchase_id');
    }

    public function unitSale()
    {
        return $this->belongsTo(Unit::class, 'unit_sale_id');
    }

    public function brand()
    {
        return $this->belongsTo(Brand::class);
    }
    
    
    
    
    public function getImageAttribute($value)
    {
        
        return $value ? URL::to('/') . '/' . $value : URL::to('/') . '/images/products/no_image.png';
    }
}
