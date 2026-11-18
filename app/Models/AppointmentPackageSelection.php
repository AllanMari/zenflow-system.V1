<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;

class AppointmentPackageSelection extends Model
{
    protected $fillable = [
        'appointment_id',
        'choice_group_id',
        'selected_service_id',
        'group_label_snapshot',
        'selected_service_name_snapshot',
    ];

    public function appointment()
    {
        return $this->belongsTo(Appointment::class);
    }

    public function choiceGroup()
    {
        return $this->belongsTo(PackageChoiceGroup::class, 'choice_group_id');
    }

    public function selectedService()
    {
        return $this->belongsTo(Service::class, 'selected_service_id');
    }

    public function getChoiceGroupNameAttribute(): string
    {
        return $this->group_label_snapshot ?? ($this->choiceGroup?->label ?? 'Choice Group');
    }

    public function getSelectedNameAttribute(): string
    {
        return $this->selected_service_name_snapshot ?? ($this->selectedService?->name ?? 'Selected Service');
    }
}
