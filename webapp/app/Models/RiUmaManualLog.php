<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiUmaManualLog extends Model
{
    protected $table = 'ri_uma_manual_logs';

    protected $fillable = [
        'uma_id',
        'ketto_num',
        'source',
        'race_date',
        'meta',
    ];
}
