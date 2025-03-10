<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Reservation extends Model
{
    
    protected $fillable = [
        'from_date',
        'to_date',
        'user_id',
        'parking_id',
    ];
}
