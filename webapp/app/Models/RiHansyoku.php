<?php

// app/Models/Hansyoku.php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiHansyoku extends Model
{
    protected $table = 'ri_hansyoku';
    protected $primaryKey = 'id'; // Laravelは便宜上idを使う
    public $incrementing = true;
    protected $keyType = 'int';
    public $timestamps = true;

    protected $fillable = [
        'RecordSpec',
        'HansyokuNum',
        'KettoNum',
        'DelKubun',
        'Bamei',
        'BameiKana',
        'BameiEng',
        'BirthYear',
        'SexCD',
        'HinsyuCD',
        'KeiroCD',
        'HansyokuMochiKubun',
        'ImportYear',
        'SanchiName',
        'HansyokuFNum',
        'HansyokuMNum',
    ];

    protected $guarded = [
        'DataKubun',
        'MakeDate',
        'reserved',
    ];
}
