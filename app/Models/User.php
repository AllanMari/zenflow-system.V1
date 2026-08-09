<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'first_name',
        'last_name',
        'password',
        'can_manage_schedules',
        'can_edit_landing',
        'is_active',
        'can_mark_attendance',
        'session_version',
        'password_changed_at',
        'terms_accepted_at',
        'privacy_consented_at',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'password' => 'hashed',
        'can_manage_schedules' => 'boolean',
        'can_edit_landing' => 'boolean',
        'is_active' => 'boolean',
        'can_mark_attendance' => 'boolean',
    ];

    // ========== EXISTING ==========
    
    public function roles()
    {
        return $this->belongsToMany(Role::class, 'role_user');
    }

    public function hasRole($role)
    {
        return $this->roles()->where('name', $role)->exists();
    }

    // ========== ADD THESE NEW METHODS ==========

    public function customerProfile()
    {
        return $this->hasOne(Customer::class, 'user_id');
    }

    public function services()
    {
        return $this->belongsToMany(Service::class, 'service_staff', 'user_id', 'service_id');
    }

    public function workSchedules()
    {
        return $this->hasMany(WorkSchedule::class, 'user_id');
    }

    public function appointments()
    {
        return $this->hasMany(Appointment::class, 'user_id');
    }

    public function createdAppointments()
    {
        return $this->hasMany(Appointment::class, 'created_by');
    }

    public function isAdmin()
    {
        return $this->hasRole('admin');
    }

    public function isReceptionist()
    {
        return $this->hasRole('receptionist');
    }

    public function isStaff()
    {
        return $this->hasRole('staff');
    }

    public function isCustomer()
    {
        return $this->hasRole('customer');
    }

    public function getFullNameAttribute()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    public function scheduleExceptions()
    {
        return $this->hasMany(ScheduleException::class);
    }

    public function attendances()
    {
        return $this->hasMany(Attendance::class);
    }

    public function canMarkAttendance(): bool
    {
        if ($this->roles->contains('name', 'admin')) return true;
        if ($this->roles->contains('name', 'receptionist') && $this->can_mark_attendance) return true;
        return false;
    }
}