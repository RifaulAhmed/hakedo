<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PreformIncomingQC extends Model
{
    use HasFactory;

    protected $table = 'preform_incoming_qc';

    protected $fillable = [
        'date',
        'dn_number',
        'product_name',
        // 'supplier_id',
        'supplier_name',
        'no_of_boxes',
        // 'parameters',
        'visual',
        'wright',
        'thick&thin',
        'mouth_inside',
        'mouth_outside',
        // 'standard',
        'result_1',
        'result_2',
        'status',
    ];

    public function provider()
    {
        return $this->belongsTo(Provider::class, 'supplier_id');
    }
}

