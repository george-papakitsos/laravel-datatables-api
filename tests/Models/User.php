<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models;

use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class User extends Model
{
    use HasFactory;

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): Factory
    {
        return UserFactory::new();
    }

    /**
     * Relationships
     */
    public function country(): BelongsTo
    {
        return $this->belongsTo(Locations\Country::class);
    }

    public function countryContinent(): BelongsTo
    {
        return $this->country();
    }

    public function userLogins(): HasMany
    {
        return $this->hasMany(UserLogin::class);
    }

    public function userNameAndEmail(): HasOne
    {
        return $this->hasOne(User::class, 'id', 'id');
    }

    public function latestUserLogins(): HasOne
    {
        return $this->userLogins()->one()->ofMany('when', 'max');
    }

    public function taggable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scopes
     */
    public function scopeSearch(Builder $query, string $term): void
    {
        $query->whereAny(['name', 'email'], 'LIKE', '%'.$term.'%');
    }

    public function scopeTest(Builder $query): void
    {
        $query->where('id', 1);
    }

    public function scopeByEmail(Builder $query, string $value): void
    {
        $query->where('email', $value);
    }

    public function scopeByNameAndEmail(Builder $query, string $name, string $email): void
    {
        $query->where('name', $name)->where('email', $email);
    }

    /**
     * Datatable fields
     */
    public function getDatatablesData(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'created_at' => $this->created_at->toDateTimeString(),
            'updated_at' => $this->updated_at->toDateTimeString(),
            'country' => $this->country->name ?? null,
            'userLogins' => $this->userLogins->count(),
            'settings' => $this->settings,
            'userNameAndEmail' => $this->name.' '.$this->email,
            'latestUserLogins' => $this->latestUserLogins?->when,
            'countryContinent' => $this->country->continent->name ?? null,
            'taggable' => $this->taggable->name ?? null,
        ];
    }

    /**
     * Datatable related fields for correct sorting & column searching
     */
    public function getRelationFields(): array
    {
        $filtersConfig = config('datatables.filters');

        return [
            'country' => ['name', implode($filtersConfig['date_field_prefix']).$filtersConfig['date_format'].$filtersConfig['date_field_prefix']['delimiter'].'founded_at'],
            'userLogins' => [],
            'userNameAndEmail' => ['name', 'email'],
            'latestUserLogins' => ['when'],
            'countryContinent' => [['continent', 'name']],
            'taggable' => [
                ['models' => [Locations\Country::class], 'fields' => ['name']],
                ['models' => [Locations\Continent::class], 'fields' => ['name', 'abbreviation']],
            ],
        ];
    }
}
