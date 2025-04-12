<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StampCorrectionRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'attendance_id',
        'date',
        'clock_in',
        'clock_out',
        'break_start',
        'break_end',
        'original_date',
        'original_clock_in',
        'original_clock_out',
        'original_break_start',
        'original_break_end',
        'original_reason',
        'reason',
        'status',
        'approved_at',
        'approved_by',
        'rejected_at'
    ];

    protected $casts = [
        'date' => 'date',
        'clock_in' => 'datetime',
        'clock_out' => 'datetime',
        'break_start' => 'array',
        'break_end' => 'array',
        'original_date' => 'date',
        'original_break_start' => 'array',
        'original_break_end' => 'array',
        'approved_at' => 'datetime',
        'rejected_at' => 'datetime'
    ];

    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }
}
