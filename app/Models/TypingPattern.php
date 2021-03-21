<?php

namespace App\Models;

use App\Traits\UsesUuid;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypingPattern extends Model
{
    use HasFactory, UsesUuid;

    protected $fillable = [
        'device_type', 'pattern_type', 'text_id'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
