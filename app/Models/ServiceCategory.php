<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ServiceCategory extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'color', 'is_active', 'show_on_landing','deposit_percentage_min',   // <-- FIX
        'deposit_percentage_max',];

    protected $casts = [
        'is_active' => 'boolean',
        'deposit_percentage_min' => 'integer',  // <-- FIX
        'deposit_percentage_max' => 'integer',
    ];

    // Services in this category
    public function services()
    {
        return $this->hasMany(Service::class, 'category_id');
    }

    // Active services only
    public function activeServices()
    {
        return $this->services()->where('is_active', true);
    }
}