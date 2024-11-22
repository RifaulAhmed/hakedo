<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    use HasFactory;

    protected $dates = ['deleted_at'];

    protected $fillable = [
    'id','user_id','username','code','Ref','email','city','phone', 'mobile', 'address','status','image', 'customer_name', 'billing_address', 'delivery_address', 'contact_person', 'email', 'phone', 'billing_address_city', 'office_number'
    ];

    protected $casts = [
        'id' => 'integer',
        'user_id' => 'integer',
        'status' => 'integer',
    ];


    public function projects()
    {
        return $this->hasMany('App\Models\Project');
    }

    public function user()
    {
        return $this->belongsTo('App\Models\User');
    }

}
