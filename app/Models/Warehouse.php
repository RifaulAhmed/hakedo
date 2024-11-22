<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Warehouse extends Model
{
    protected $dates = ['deleted_at'];

    protected $fillable = [
        'name','code', 'mobile', 'country', 'city', 'email', 'zip', 'Ref',
    ];
    

    // public function assignedUsers()
    // {
    //     return $this->belongsToMany('App\Models\User');
    // }
    
    public function assignedUsers()
{
    return $this->belongsToMany(User::class, 'user_warehouse', 'warehouse_id', 'user_id');
}


}
