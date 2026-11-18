<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PackageChoiceGroup extends Model
{
    protected $fillable = ['service_id', 'label', 'is_required', 'max_selections'];

    protected $casts = [
        'is_required' => 'boolean',
        'max_selections' => 'integer',
    ];

    public function package()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function options()
    {
        return $this->hasMany(PackageChoiceOption::class, 'choice_group_id')->orderBy('sort_order');
    }

    public function getNameAttribute(): string
    {
        return $this->label;
    }
}
