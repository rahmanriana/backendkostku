<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Room extends Model
{
    use HasFactory;

    protected $fillable = [
        'kost_id',
        'name',
        'price',
        'is_available',
        'size',
        'capacity',
        'description',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'is_available' => 'boolean',
        'size' => 'integer',
        'capacity' => 'integer',
    ];

    public function kost()
    {
        return $this->belongsTo(Kost::class);
    }

    public function facilities()
    {
        return $this->belongsToMany(Facility::class, 'room_facilities');
    }

    public function photos()
    {
        return $this->hasMany(RoomPhoto::class);
    }
}
