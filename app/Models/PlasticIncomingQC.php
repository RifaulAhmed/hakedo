<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PlasticIncomingQC extends Model
{
    use HasFactory;

    protected $table = 'plastic_incoming_qc';

    protected $fillable = [
        'date',
        'dn_number',
        'product_name',
        'supplier_name',
        'no_of_bag',
        'parameter',
        'standard',
        'result',
        'status',
    ];

    public function provider(){

        return $this->belongsTo(Provider::class, 'supplier_name');
    }
}
