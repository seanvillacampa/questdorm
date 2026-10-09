<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MeterReading extends Model
{
    protected $fillable = [
        'room_id',
        'reading_month',
        'previous_kwh',
        'current_kwh',
        'kwh_used',
        'recorded_by',
    ];

    public function room()
    {
        return $this->belongsTo(Room::class);
    }

    public function recordedBy()
    {
        return $this->belongsTo(User::class, 'recorded_by');
    }
}
