<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LoadingReport extends Model
{
    use HasFactory;

    protected $fillable = [
        'destination',
        'date', 
        'do_number', 
        'license_plate_number', 
        'dn_number',
        'start_finish', 
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
        'total'
    ];
}
