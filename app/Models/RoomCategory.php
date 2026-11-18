<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class RoomCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'code',
        'description',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function rooms()
    {
        return $this->belongsToMany(Room::class, 'room_category_room')
                    ->withTimestamps();
    }

    public function services()
    {
        return $this->hasMany(Service::class, 'room_category_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
