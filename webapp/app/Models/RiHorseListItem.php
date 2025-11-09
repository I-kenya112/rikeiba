<?php

// app/Models/RiHorseList.php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiHorseListItem extends Model
{
    use HasFactory;

    protected $fillable = [
        'list_id',
        'horse_id',
        'horse_name',
        'order_no',
    ];

    public function list()
    {
        return $this->belongsTo(RiHorseList::class, 'list_id');
    }
}
