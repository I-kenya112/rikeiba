<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiUmaRace extends Model
{
    protected $table = 'ri_uma_race';

    // 複合主キー（Year, JyoCD, Kaiji, Nichiji, RaceNum, KettoNum）
    protected $primaryKey = ['Year','JyoCD','Kaiji','Nichiji','RaceNum','KettoNum'];
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * 出走馬 → レース
     */
    public function race()
    {
        return $this->belongsTo(
            RiRace::class,
            ['Year','JyoCD','Kaiji','Nichiji','RaceNum'],
            ['Year','JyoCD','Kaiji','Nichiji','RaceNum']
        );
    }

    /**
     * 出走馬 → 馬マスタ (ri_uma)
     */
    public function uma()
    {
        return $this->belongsTo(
            RiUma::class,
            'KettoNum',
            'KettoNum'
        );
    }
}
