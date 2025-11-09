<?php

// app/Models/RiInbreedRatio.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiInbreedRatio extends Model
{
    protected $table = 'ri_inbreed_ratio';
    protected $primaryKey = 'id'; // Laravelは便宜上idを使う
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'horse_id',
        'horse_name',
        'ancestor_id',
        'ancestor_name',
        'generation_paths',
        'cross_count',
        'blood_share_sum',
        'cross_ratio_percent',
        'inbreed_degree',
        'source',
    ];

    protected $guarded = [
        'DataKubun',
        'MakeDate',
        'reserved',
    ];
}
