<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AttendanceRecordChange extends Model
{
    /**
     * @var list<string>
     */
    protected $fillable = [
        'attendance_session_id',
        'student_id',
        'changed_by',
        'from_status',
        'to_status',
        'reason',
    ];

    /**
     * @return BelongsTo<User, $this>
     */
    public function changedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'changed_by');
    }
}
