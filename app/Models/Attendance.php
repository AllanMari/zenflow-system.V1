<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Attendance extends Model
{
    protected $fillable = ['user_id', 'date', 'status', 'check_in', 'check_out', 'marked_by', 'notes'];
    protected $casts = ['date' => 'date'];

    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function marker(): BelongsTo { return $this->belongsTo(User::class, 'marked_by'); }

    public static function todayFor(int $userId): ?self
    {
        return self::where('user_id', $userId)->whereDate('date', today())->first();
    }
}