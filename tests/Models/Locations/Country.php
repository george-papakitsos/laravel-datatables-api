<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models\Locations;

use Database\Factories\CountryFactory;
use GPapakitsos\LaravelDatatables\Tests\Models;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'founded_at'];
    public $timestamps = false;
    protected $casts = [
        'founded_at' => 'date',
    ];

    /**
     * Create a new factory instance for the model.
     *
     * @return \Illuminate\Database\Eloquent\Factories\Factory
     */
    protected static function newFactory()
    {
        return CountryFactory::new();
    }

    /**
     * Relationships
     */
    public function users()
    {
        return $this->hasMany(Models\User::class);
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
        ];
    }
}
