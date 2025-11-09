<?php

// app/Models/RiHorseList.php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RiHorseList extends Model
{
    use HasFactory;

    protected $fillable = ['user_id', 'title', 'description', 'is_shared'];

    public function items()
    {
        return $this->hasMany(RiHorseListItem::class, 'list_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
