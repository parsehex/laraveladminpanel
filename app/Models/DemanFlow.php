<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class DemanFlow extends Model
{
    protected $fillable = [
        'slug',
        'name',
    ];

    public function prompts(): HasMany
    {
        return $this->hasMany(DemanFlowPrompt::class)->orderBy('sort_order')->orderBy('id');
    }
}
