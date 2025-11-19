<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RiRace extends Model
{
    protected $table = 'ri_race';

    // 複合主キー（Year, JyoCD, Kaiji, Nichiji, RaceNum）
    protected $primaryKey = ['Year', 'JyoCD', 'Kaiji', 'Nichiji', 'RaceNum'];
    public $incrementing = false;
    protected $keyType = 'string';

    public $timestamps = false;

    protected $guarded = [];

    /**
     * レース → 出走馬
     */
    public function umaRaces()
    {
        return $this->hasMany(
            RiUmaRace::class,
            ['Year','JyoCD','Kaiji','Nichiji','RaceNum'],
            ['Year','JyoCD','Kaiji','Nichiji','RaceNum']
        );
    }
}
