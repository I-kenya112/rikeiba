<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiBloodlineDetail extends Model
{
    protected $table = 'ri_bloodline_detail';

    protected $fillable = [
        'bloodline_id',
        'line_key_detail',
        'line_name',
        'root_horse_id',
        'root_hansyoku_num',
        'description',
    ];

    public function bloodline()
    {
        return $this->belongsTo(RiBloodline::class, 'bloodline_id');
    }
}
