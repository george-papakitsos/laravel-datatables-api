<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models;

use Database\Factories\UserLoginFactory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserLogin extends Model
{
    use HasFactory;

    public $timestamps = false;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return UserLoginFactory::new();
    }

    /**
     * Relationships
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
