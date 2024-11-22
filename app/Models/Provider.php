<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Provider extends Model
{
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'user_id', 'Ref','company_name', 'first_name', 'middle_name', 'last_name', 'email', 'phone', 'billing_address', 'billing_address_no', 'billing_address_rt', 'billing_address_rw', 'billing_address_postcode', 'billing_address_urbanward', 'billing_address_district',  'billing_address_province', 'tax_number', 'billing_address_city', 'mobile'
    ];

    protected $casts = [
        'user_id' => 'integer',
    ];


    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }
}
