<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class PackageChoiceOption extends Model
{
    protected $fillable = ['choice_group_id', 'service_id', 'sort_order'];

    public function choiceGroup()
    {
        return $this->belongsTo(PackageChoiceGroup::class, 'choice_group_id');
    }

    public function service()
    {
        return $this->belongsTo(Service::class, 'service_id');
    }

    public function getNameAttribute(): string
    {
        return $this->service?->name ?? ('Option #' . $this->id);
    }

    public function getDurationMinutesAttribute(): ?int
    {
        return $this->service?->duration_minutes;
    }
}
