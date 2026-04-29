<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Contact extends Model
{
    use HasFactory;

    protected $fillable = [
        'seeker_id',
        'owner_id',
        'kost_id',
        'message',
        'status',
    ];

    public function seeker()
    {
        return $this->belongsTo(User::class, 'seeker_id');
    }

    public function owner()
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function kost()
    {
        return $this->belongsTo(Kost::class);
    }
}
