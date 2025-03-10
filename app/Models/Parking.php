<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Parking extends Model
{
    
    use HasFactory;

    protected $fillable = [
        'name',
        'price',
        'limit',
    ];


    // Relationships
    public function reservations()
    {
        return $this->hasMany(Reservation::class, 'parking_id');
    }
}
