<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiPedigree extends Model
{
    protected $table = 'ri_pedigree';
    protected $primaryKey = 'id'; // Laravelは便宜上idを使う
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'horse_id',
        'horse_name',
        'relation_path',
        'generation',
        'ancestor_id_uma',
        'ancestor_id_hansyoku',
        'ancestor_name',
        'blood_share',
        'source'
    ];
}
