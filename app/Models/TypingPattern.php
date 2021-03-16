<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TypingPattern extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_type', 'pattern_type'
    ];

    public function user()
    {
        return $this->belongsTo(User::class, "user_id");
    }
}
