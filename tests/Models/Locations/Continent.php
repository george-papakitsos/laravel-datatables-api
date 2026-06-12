<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models\Locations;

use GPapakitsos\LaravelDatatables\Tests\Models;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

class Continent extends Model
{
    protected $fillable = ['name', 'abbreviation'];
    public $timestamps = false;

    /**
     * Relationships
     */
    public function countries(): HasMany
    {
        return $this->hasMany(Country::class);
    }

    public function tags(): MorphMany
    {
        return $this->morphMany(Models\User::class, 'taggable');
    }
}
