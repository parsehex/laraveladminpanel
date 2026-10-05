<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Suggestion extends Model
{
    public const KIND_SUGGESTION = 'suggestion';

    public const KIND_INPUT_REQUEST = 'input_request';

    protected $fillable = [
        'kind',
        'user_id',
        'username',
        'suggestion',
        'page_url',
        'urgency',
        'status',
        'responses',
        'completed_by',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'responses' => 'array',
            'completed_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    public function isInputRequest(): bool
    {
        return $this->kind === self::KIND_INPUT_REQUEST;
    }

    #[Scope]
    protected function suggestions(Builder $query): void
    {
        $query->where('kind', self::KIND_SUGGESTION);
    }

    #[Scope]
    protected function inputRequests(Builder $query): void
    {
        $query->where('kind', self::KIND_INPUT_REQUEST);
    }
}
