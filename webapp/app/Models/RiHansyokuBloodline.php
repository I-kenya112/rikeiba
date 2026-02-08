<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiHansyokuBloodline extends Model
{
    protected $table = 'ri_hansyoku_bloodline';
    protected $primaryKey = 'hansyoku_num';
    public $incrementing = false;
    protected $keyType = 'string';

    protected $guarded = [];
}
