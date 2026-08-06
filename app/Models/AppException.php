<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AppException extends Model
{
    protected $table = 'exceptions';

    protected $guarded = [];

    protected function casts(): array
    {
        return [
            'trace' => 'array',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
