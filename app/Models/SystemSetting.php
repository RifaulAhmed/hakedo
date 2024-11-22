<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SystemSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'default_email',
        'change_logo',
        'company_name',
        'company_phone',
        'developed_by',
        'app_name',
        'default_warehouse_id',
        'company_address',
    ];

    protected $casts = [
        'change_logo' => 'string',
    ];
}
