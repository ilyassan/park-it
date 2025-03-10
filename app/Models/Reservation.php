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


    // Relationships
    public function user()
    {
        return $this->belongsTo(User::class, 'user_id');
    }

    public function parking()
    {
        return $this->belongsTo(Parking::class, 'parking_id');
    }
}
