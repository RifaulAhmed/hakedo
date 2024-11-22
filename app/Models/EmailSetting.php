<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'mail_mailer', 'mail_host', 'mail_from_name', 'mail_from_address', 'mail_port', 'mail_username', 'mail_password', 'mail_encryption'
    ];
}
