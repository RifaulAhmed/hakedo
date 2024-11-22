<?php

namespace App\Models;

use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;
use Laravel\Sanctum\HasApiTokens;

class User extends Authenticatable
{
    use Notifiable , HasRoles, HasApiTokens;
    // protected $dates = ['deleted_at'];


    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'username', 'email', 'password', 'status', 'avatar','role_users_id','is_all_warehouses', 'company_id', 'join_date', 'resign_date', 'nik_ktp', 'dob', 'gender', 'address', 'locker_no', 'distribution_date', 'remember_token',
    ];

    /**
     * The attributes that should be hidden for arrays.
     *
     * @var array
     */
    protected $hidden = [
        'password', 'remember_token',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
        'role_users_id' => 'integer',
        'status' => 'integer',
        'is_all_warehouses' => 'integer',
    ];


    public function RoleUser()
	{
        return $this->hasone('Spatie\Permission\Models\Role','id',"role_users_id");
    }
    
    // public function assignedWarehouses()
    // {
    //     return $this->belongsToMany('App\Models\Warehouse');
    // }
 public function assignedWarehouses()
    {
        return $this->belongsToMany(Warehouse::class, 'user_warehouse', 'user_id', 'warehouse_id')
                    ->withPivot('user_id', 'warehouse_id');
    }


   public function assignRoleById($roleId)
    {
        $role = Role::find($roleId);

        if ($role) {
            $this->role_users_id = $role->id;
            $this->save();
        } else {
            throw new \Exception('There is no role with id `' . $roleId . '`.');
        }
    }
}
