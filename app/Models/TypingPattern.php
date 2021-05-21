<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypingPattern extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'device_type',
        'pattern_type',
        'text_id',
        'compared_samples',
        'previous_samples',
        'confidence',
        'confidence_interval',
        'score',
        'net_score',
        'result',
        'success',
        'message_code',
        'position',
        'enrolled_position',
        'selected_position',
        'custom_field',
        'text_length',
        'experiment_type',
        'user_email'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
