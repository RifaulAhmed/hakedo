<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WRRForm extends Model
{
    use HasFactory;

    protected $table = 'wrr_forms';

    protected $fillable = [
        'wrr_no', 'Ref', 'date', 'dn_number', 'po_number', 'supplier_id', 'supplier_name',
        'material_id', 'material_description', 'dn_quantity', 'received_quantity',
    ];
    
  public function provider()
{
    return $this->belongsTo(Provider::class, 'supplier_id', 'id');
}


}
