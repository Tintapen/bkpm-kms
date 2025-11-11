<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\HasAuditTrail;

class MailSetting extends Model
{
    use HasFactory;
    use HasAuditTrail;

    protected $fillable = [
        'mailer', 'host', 'port', 'username', 'password', 'encryption', 'from_address', 'from_name'
    ];
}
