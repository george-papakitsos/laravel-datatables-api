<?php

namespace GPapakitsos\LaravelDatatables\Tests\Models;

use Illuminate\Database\Eloquent\Model;

class User extends Model
{
	/**
	 * Scopes
	 */
	public function scopeSearch($query, $term)
	{
		return $query->where('name', 'LIKE', '%'.$term.'%')->orWhere('email', 'LIKE', '%'.$term.'%');
	}

	public function scopeTest($query)
	{
		return $query->where('id', 1);
	}

	public function scopeByEmail($query, $value)
	{
		return $query->where('email', $value);
	}

	/**
	 * Datatable fields
	 *
	 * @return array
	 */
	public function getDatatablesData()
	{
		return [
			'id'			=> $this->id,
			'name'			=> $this->name,
			'email'			=> $this->email,
			'created_at'	=> $this->created_at->toDateTimeString(),
			'updated_at'	=> $this->updated_at->toDateTimeString(),
		];
	}
}
