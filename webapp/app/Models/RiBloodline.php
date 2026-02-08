<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiBloodline extends Model
{
    protected $table = 'ri_bloodline';

    protected $fillable = [
        'line_key',
        'line_name',
        'root_hansyoku_num',
        'description',
    ];

    public function details()
    {
        return $this->hasMany(RiBloodlineDetail::class, 'bloodline_id');
    }
}
