<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class Service extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_id',
        'name',
        'duration_minutes',
        'price',
        'discount_price',
        'description',
        'landing_description',
        'image',
        'is_package',
        'included_services',        // ← ADD THIS
        'requires_prepayment',
        'is_active',
        'show_on_landing',
        'deposit_percentage_min',
        'deposit_percentage_max',
        'requires_room',
        'room_category_id',
        'code',
        'participants_required',
        'therapists_required',
    ];

    protected $casts = [
        'is_package' => 'boolean',
        'included_services' => 'array',     // ← ADD THIS
        'requires_prepayment' => 'boolean',
        'is_active' => 'boolean',
        'show_on_landing' => 'boolean',
        'price' => 'decimal:2',
        'deposit_percentage_min' => 'integer',
        'deposit_percentage_max' => 'integer',
        'requires_room' => 'boolean',
        'participants_required' => 'integer',
        'therapists_required' => 'integer',
        // duration_minutes intentionally NOT cast so null is preserved as null
    ];

    /**
     * Default attribute values so new model instances surface correct
     * defaults even before the row is saved to the database.
     */
    protected $attributes = [
        'participants_required' => 1,
        'therapists_required'   => 1,
    ];

    /**
     * Mutator to ensure participants_required cannot be set to 0 or negative.
     */
    public function setParticipantsRequiredAttribute($value): void
    {
        $intVal = (int) $value;
        if ($intVal < 1) {
            throw new \InvalidArgumentException('participants_required must be a positive integer greater than or equal to 1.');
        }
        $this->attributes['participants_required'] = $intVal;
    }

    /**
     * Mutator to ensure therapists_required cannot be set to 0 or negative.
     */
    public function setTherapistsRequiredAttribute($value): void
    {
        $intVal = (int) $value;
        if ($intVal < 1) {
            throw new \InvalidArgumentException('therapists_required must be a positive integer greater than or equal to 1.');
        }
        $this->attributes['therapists_required'] = $intVal;
    }

    public function category()
    {
        return $this->belongsTo(ServiceCategory::class, 'category_id');
    }

    public function appointments()
    {
        return $this->belongsToMany(Appointment::class, 'appointment_services')
                    ->withPivot('price_at_booking');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeSingle($query)
    {
        return $query->where('is_package', false);
    }

    public function requiresPrepayment()
    {
        return $this->requires_prepayment;
    }

    public function roomCategory()
    {
        return $this->belongsTo(RoomCategory::class, 'room_category_id');
    }

    /**
     * Choice groups for this package service.
     */
    public function choiceGroups()
    {
        return $this->hasMany(PackageChoiceGroup::class, 'service_id');
    }

    /**
     * Get a fully-qualified, publicly accessible URL for the service image.
     *
     * Resolution order:
     *  1. External URLs are passed through unchanged.
     *  2. File found on the `uploads` disk  → uploads URL.
     *  3. File found on the `public` disk   → storage URL (legacy symlink).
     *  4. File not found anywhere           → null (caller renders a fallback).
     */
    public function getImageUrlAttribute(): ?string
    {
        if (!$this->image) {
            return null;
        }

        // Pass through external URLs (http/https)
        if (preg_match('/^https?:\/\//i', $this->image)) {
            return $this->image;
        }

        // Normalize: strip any disk-prefix so we always work with a relative key
        // e.g. "/uploads/services/abc.jpg" → "services/abc.jpg"
        //      "storage/services/abc.jpg"  → "services/abc.jpg"
        $cleanPath = ltrim(
            preg_replace('#^/?(storage|uploads)/#i', '', $this->image),
            '/'
        );

        // Check the new uploads disk first
        if (Storage::disk('uploads')->exists($cleanPath)) {
            return Storage::disk('uploads')->url($cleanPath);
        }

        // Fall back to legacy public disk (storage/ symlink)
        if (Storage::disk('public')->exists($cleanPath)) {
            return Storage::disk('public')->url($cleanPath);
        }

        // File is missing — return null; the view handles the fallback display
        return null;
    }
}