<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models\Locations;

use Database\Factories\CountryFactory;
use GPapakitsos\LaravelDatatables\Tests\Models;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'founded_at', 'continent_id'];
    public $timestamps = false;
    protected $casts = [
        'founded_at' => 'date',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return CountryFactory::new();
    }

    /**
     * Relationships
     */
    public function continent(): BelongsTo
    {
        return $this->belongsTo(Continent::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(Models\User::class);
    }

    public function tags(): MorphMany
    {
        return $this->morphMany(Models\User::class, 'taggable');
    }

    /**
     * Datatable fields
     */
    public function getDatatablesData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'founded_at' => $this->founded_at,
            'continent' => $this->continent->name ?? null,
        ];
    }

    /**
     * Datatable related fields for correct sorting & column searching
     */
    public function getRelationFields(): array
    {
        return [
            'continent' => ['name', 'abbreviation'],
        ];
    }
}
