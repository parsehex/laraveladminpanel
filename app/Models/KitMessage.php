<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class KitMessage extends Model
{
    protected $fillable = [
        'assignment_id',
        'sender_id',
        'message',
    ];

    public function assignment(): BelongsTo
    {
        return $this->belongsTo(KitAssignment::class, 'assignment_id');
    }

    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }
}
