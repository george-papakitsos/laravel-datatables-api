<?php

/**
 * jQuery DataTables API for Laravel
 *
 * @author George Papakitsos <papakitsos_george@yahoo.gr>
 * @copyright George Papakitsos
 */

namespace GPapakitsos\LaravelDatatables;

use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

class Datatables
{
    /**
     * Holds all input data
     */
    protected array $options = [];

    /**
     * The PDO driver name
     */
    protected string $driver;

    /**
     * The Eloquent model
     *
     * @var \Illuminate\Database\Eloquent\Model
     */
    protected $model;

    /**
     * The query builder instance
     */
    protected \Illuminate\Database\Eloquent\Builder $queryBuilder;

    /**
     * Holds the relation fields of model
     */
    protected array $relations;

    /**
     * Count of all model's records
     */
    protected int $totalCount;

    /**
     * Count of filtered model's records
     */
    protected int $filteredCount;

    /**
     * The constructor
     *
     * @throws \Symfony\Component\HttpKernel\Exception\HttpException
     */
    public function __construct(Request $request, string $model)
    {
        $this->options = $request->all();

        foreach (['columns', 'length'] as $key) {
            if (! array_key_exists($key, $this->options)) {
                abort(400, "Key '".$key."' must be provided in request data");
            }
        }

        $model = config('datatables.models_namespace').$model;
        $this->model = new $model;

        if (! method_exists($this->model, 'getDatatablesData')) {
            abort(400, 'Method getDatatablesData is not set in '.get_class($this->model));
        }

        $this->queryBuilder = $this->model->query();

        $this->relations = method_exists($this->model, 'getRelationFields') ? $this->model->getRelationFields() : [];

        $this->driver = $this->queryBuilder->getConnection()->getDriverName();
    }

    /**
     * Builds the JSON response
     */
    public function response(): \Illuminate\Http\JsonResponse
    {
        if (! empty($this->options['scope'])) {
            $this->applyScope();
        }

        if (! empty($this->options['extraWhere'])) {
            $this->applyExtraWhere();
        }

        if (method_exists($this->model, 'scopeEagerLoading')) {
            $this->queryBuilder->eagerLoading();
        }

        if (isset($this->options['order']) && (! empty($this->options['order'][0]['column']) || $this->options['order'][0]['column'] === '0')) {
            $this->sortByColumn();
        }

        $this->totalCount = $this->queryBuilder->count();

        $searchOccurred = $this->search();

        $searchByColumnOccurred = $this->searchByColumn();

        $this->filteredCount = $searchOccurred || $searchByColumnOccurred ? $this->queryBuilder->count() : $this->totalCount;

        return response()->json($this->getFormatedData());
    }

    /**
     * Applies a scope to the query builder
     */
    private function applyScope(): void
    {
        $scopeOpt = $this->options['scope'];

        if (is_array($scopeOpt)) {
            $scope = $scopeOpt[0];
            if (method_exists($this->model, 'scope'.ucwords($scope))) {
                $args = array_slice($scopeOpt, 1);
                $this->queryBuilder->$scope(...$args);
            }
        } else {
            if (method_exists($this->model, 'scope'.ucwords($scopeOpt))) {
                $this->queryBuilder->$scopeOpt();
            }
        }
    }

    /**
     * Applies an extra where condition to the query builder
     */
    private function applyExtraWhere(): void
    {
        foreach ($this->options['extraWhere'] as $field => $value) {
            is_array($value)
                ? $this->queryBuilder->whereIn($field, $value)
                : $this->queryBuilder->where($field, (Str::startsWith($value, '%') || Str::endsWith($value, '%') ? 'LIKE' : '='), $value);
        }
    }

    /**
     * Applies ORDER BY to the query builder
     */
    private function sortByColumn(): void
    {
        $field = $this->options['columns'][$this->options['order'][0]['column']]['data'] ?? null;
        if ($field === null) {
            return;
        }
        $direction = $this->options['order'][0]['dir'] ?? 'asc';

        if (! isset($this->relations[$field])) { // if field exists on model
            $this->queryBuilder->orderBy($field, $direction);
        } else { // if field is relation of model
            $relation = $this->model->$field();
            $table = $this->model->getTable();
            $otherTable = $relation->getRelated()->getTable();

            if ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsTo) {
                $this->queryBuilder
                    ->leftJoin($otherTable, $relation->getQualifiedForeignKeyName(), '=', $relation->getQualifiedOwnerKeyName())
                    ->select($table.'.*');
                foreach ($this->relations[$field] as $otherField) {
                    if (is_string($otherField)) {
                        if (Str::startsWith($otherField, implode(config('datatables.filters.date_field_prefix')))) {
                            $otherField = Str::afterLast($otherField, config('datatables.filters.date_field_prefix.delimiter'));
                            $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
                        } else {
                            $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
                        }
                    } else {
                        $relationThrough = $relation->getRelated()->{$otherField[0]}();
                        $relationThroughOtherTable = $relationThrough->getRelated()->getTable();

                        $this->queryBuilder
                            ->leftJoin($relationThroughOtherTable, $relationThrough->getQualifiedForeignKeyName(), '=', $relationThrough->getQualifiedOwnerKeyName())
                            ->orderBy($relationThroughOtherTable.'.'.$otherField[1], $direction);
                    }
                }
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\BelongsToMany) {
                $this->queryBuilder
                    ->leftJoin($relation->getTable(), $relation->getQualifiedForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName())
                    ->leftJoin($relation->getRelated()->getTable(), $relation->getQualifiedRelatedPivotKeyName(), '=', $relation->getRelated()->getTable().'.'.$relation->getRelated()->getKeyName())
                    ->select($table.'.*')
                    ->distinct();
                foreach ($this->relations[$field] as $otherField) {
                    $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
                }
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasMany) {
                $this->queryBuilder->orderBy(DB::raw('(SELECT COUNT(*) FROM `'.$otherTable.'` WHERE '.$relation->getQualifiedForeignKeyName().' = '.$relation->getQualifiedParentKeyName().')'), $direction);
            } elseif ($relation instanceof \Illuminate\Database\Eloquent\Relations\HasOne) {
                foreach ($this->relations[$field] as $otherField) {
                    $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
                }
            }
        }
    }

    /**
     * Searches the collection
     */
    private function search(): bool
    {
        if (empty($this->options['search']['value'])) {
            return false;
        }

        if (! method_exists($this->model, 'scopeSearch')) {
            abort(400, 'Method scopeSearch is not set in '.get_class($this->model));
        }

        $terms = explode(' ', trim($this->options['search']['value']));

        foreach ($terms as $term) {
            $term = trim($term);
            if (! empty($term)) {
                $this->queryBuilder->where(function ($query) use ($term) {
                    $query->search($term);
                });
            }
        }

        return true;
    }

    /**
     * Applies datatables global search
     */
    private function searchByColumn(): bool
    {
        $table = $this->model->getTable();
        $filtersConfig = config('datatables.filters');
        $result = false;

        foreach ($this->options['columns'] as $col) {
            $searchValue = $col['search']['value'];
            if (! empty($searchValue) || $searchValue === '0') {
                $result = true;

                $field = $col['data'];
                $this->queryBuilder->where(function ($query) use ($table, $field, $searchValue, $filtersConfig) {
                    if (! isset($this->relations[$field])) { // if field exists on model
                        if (Str::contains($searchValue, $filtersConfig['date_delimiter'])) {
                            $dates = explode($filtersConfig['date_delimiter'], $searchValue);
                            if (! empty($dates[0])) {
                                $query->whereRaw(DB::raw("DATE(`$table`.`$field`) >= '".Carbon::createFromFormat($filtersConfig['date_format'], $dates[0])->toDateString()."'"));
                            }
                            if (! empty($dates[1])) {
                                $query->whereRaw(DB::raw("DATE(`$table`.`$field`) <= '".Carbon::createFromFormat($filtersConfig['date_format'], $dates[1])->toDateString()."'"));
                            }
                        } elseif (Str::contains($searchValue, $filtersConfig['null_delimiter'])) {
                            $query->where($table.'.'.$field, '')->orWhereNull($table.'.'.$field);
                        } elseif (Str::startsWith($searchValue, '|') && Str::endsWith($searchValue, '|')) {
                            $query->where($table.'.'.$field, trim($searchValue, '|'));
                        } elseif (Schema::hasTable($table) && Schema::getColumnType($table, $field) == 'json') {
                            $query->whereRaw('LOWER(JSON_EXTRACT('.$table.'.'.$field.', "$.*")) LIKE ?', ['%'.strtolower($searchValue).'%']);
                        } else {
                            $query->where($table.'.'.$field, 'LIKE', '%'.$searchValue.'%');
                        }
                    } else { // if field is relation of model
                        $relation = $this->model->$field();
                        $otherTable = $relation->getRelated()->getTable();
                        if (! $relation instanceof \Illuminate\Database\Eloquent\Relations\MorphTo) {
                            if (Str::contains($searchValue, $filtersConfig['null_delimiter'])) {
                                $query->whereDoesntHave($field);
                            } else {
                                $query->whereHas($field, function ($query) use ($field, $searchValue, $otherTable, $filtersConfig) {
                                    $query->where(function ($query) use ($field, $searchValue, $otherTable, $filtersConfig) {
                                        foreach ($this->relations[$field] as $otherField) {
                                            if (is_string($otherField)) {
                                                if (Str::contains($searchValue, $filtersConfig['date_delimiter'])) {
                                                    $dates = explode($filtersConfig['date_delimiter'], $searchValue);
                                                    if (! empty($dates[0])) {
                                                        $query->whereRaw(DB::raw("DATE(`$otherTable`.`$otherField`) >= '".Carbon::createFromFormat($filtersConfig['date_format'], $dates[0])->toDateString()."'"));
                                                    }
                                                    if (! empty($dates[1])) {
                                                        $query->whereRaw(DB::raw("DATE(`$otherTable`.`$otherField`) <= '".Carbon::createFromFormat($filtersConfig['date_format'], $dates[1])->toDateString()."'"));
                                                    }
                                                } elseif (Str::startsWith($searchValue, '|') && Str::endsWith($searchValue, '|')) {
                                                    $query->orWhere($otherTable.'.'.$otherField, trim($searchValue, '|'));
                                                } elseif (Str::startsWith($otherField, implode($filtersConfig['date_field_prefix']))) {
                                                    $date_field_prefix_array = explode($filtersConfig['date_field_prefix']['delimiter'], $otherField);
                                                    if (count($date_field_prefix_array) !== 3) {
                                                        continue;
                                                    }

                                                    $dateFormat = strtr($date_field_prefix_array[1], [
                                                        'd' => '%d', 'j' => '%e', 'm' => '%m', 'Y' => '%Y', 'y' => '%y',
                                                    ]);
                                                    if (empty($dateFormat)) {
                                                        continue;
                                                    }

                                                    $otherField = $date_field_prefix_array[2];
                                                    $dateExpr = $this->driver === 'sqlite'
                                                        ? "strftime('".$dateFormat."', `$otherTable`.`$otherField`)"
                                                        : "DATE_FORMAT(`$otherTable`.`$otherField`, '".$dateFormat."')";
                                                    $query->orWhere(DB::raw($dateExpr), 'LIKE', '%'.$searchValue.'%');
                                                } else {
                                                    $query->orWhere($otherTable.'.'.$otherField, 'LIKE', '%'.$searchValue.'%');
                                                }
                                            } else {
                                                $query->whereHas($otherField[0], function ($query) use ($otherField, $searchValue) {
                                                    if (is_string($otherField[1])) {
                                                        $query->where($otherField[1], 'LIKE', '%'.$searchValue.'%');
                                                    } elseif (is_array($otherField[1])) {
                                                        $query->where(function ($query) use ($otherField, $searchValue) {
                                                            foreach ($otherField[1] as $otherFieldItem) {
                                                                $query->orWhere($otherFieldItem, 'LIKE', '%'.$searchValue.'%');
                                                            }
                                                        });
                                                    }
                                                });
                                            }
                                        }
                                    });
                                });
                            }
                        } else {
                            $query->where(function ($query) use ($field, $searchValue) {
                                foreach ($this->relations[$field] as $otherField) {
                                    $query->orWhereHasMorph($field, $otherField['models'], function ($query, $type) use ($otherField, $searchValue) {
                                        foreach ($otherField['fields'] as $morphFieldKey => $morphField) {
                                            $query->{$morphFieldKey == 0 ? 'where' : 'orWhere'}($morphField, 'LIKE', '%'.$searchValue.'%');
                                        }
                                    });
                                }
                            });
                        }
                    }
                });
            }
        }

        return $result;
    }

    /**
     * Formats the data for JSON response
     */
    private function getFormatedData(): array
    {
        $take = (int) $this->options['length'];
        if ($take !== -1) {
            $this->queryBuilder->take($take);
            if (isset($this->options['start'])) {
                $this->queryBuilder->skip((int) $this->options['start']);
            }
        }

        return [
            'draw' => (int) ($this->options['draw'] ?? 1),
            'recordsTotal' => $this->totalCount,
            'recordsFiltered' => $this->filteredCount,
            'data' => $this->queryBuilder->get()->map(function ($model) {
                return $model->getDatatablesData();
            }),
        ];
    }
}
