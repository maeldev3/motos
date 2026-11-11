<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AiInteraction extends Model
{
    protected $fillable = [
        'user_id', 'kind', 'question', 'answer', 'context_summary',
        'provider', 'model', 'status',
    ];

    protected function casts(): array
    {
        return ['context_summary' => 'array'];
    }
}
