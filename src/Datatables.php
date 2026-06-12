<?php

/**
 * jQuery DataTables API for Laravel
 *
 * @author George Papakitsos <papakitsos_george@yahoo.gr>
 * @copyright George Papakitsos
 */

namespace GPapakitsos\LaravelDatatables;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
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
     */
    protected Model $model;

    /**
     * The query builder instance
     */
    protected Builder $queryBuilder;

    /**
     * Holds the relation fields of model
     */
    protected array $relations = [];

    /**
     * Holds the filters configuration
     */
    protected array $filtersConfig;

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

        if (method_exists($this->model, 'getRelationFields')) {
            $this->relations = $this->model->getRelationFields();
        }

        $this->driver = $this->queryBuilder->getConnection()->getDriverName();

        $this->filtersConfig = config('datatables.filters');
    }

    /**
     * Builds the JSON response
     */
    public function response(): JsonResponse
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
                $this->queryBuilder->{$scope}(...$args);
            }
        } else {
            if (method_exists($this->model, 'scope'.ucwords($scopeOpt))) {
                $this->queryBuilder->{$scopeOpt}();
            }
        }
    }

    /**
     * Applies an extra where condition to the query builder
     */
    private function applyExtraWhere(): void
    {
        foreach ($this->options['extraWhere'] as $field => $value) {
            $this->queryBuilder->when(
                is_array($value),
                fn (Builder $query) => $query->whereIn($field, $value),
                fn (Builder $query) => $query->where($field, (Str::startsWith($value, '%') || Str::endsWith($value, '%') ? 'LIKE' : '='), $value)
            );
        }
    }

    /**
     * Applies ORDER BY to the query builder
     */
    private function sortByColumn(): void
    {
        if (empty($field = $this->options['columns'][$this->options['order'][0]['column']]['data'] ?? null)) {
            return;
        }

        $direction = $this->options['order'][0]['dir'] ?? 'asc';

        // field exists on model
        if (! isset($this->relations[$field])) {
            $this->queryBuilder->orderBy($field, $direction);

            return;
        }

        // field is relation of model
        $relation = $this->model->{$field}();
        $table = $this->model->getTable();
        $otherTable = $relation->getRelated()->getTable();

        if ($relation instanceof BelongsTo) {
            $this->queryBuilder
                ->leftJoin($otherTable, $relation->getQualifiedForeignKeyName(), '=', $relation->getQualifiedOwnerKeyName())
                ->select($table.'.*');

            foreach ($this->relations[$field] as $otherField) {
                if (is_string($otherField)) {
                    if ($this->isDateFieldWithPrefix($otherField)) {
                        $otherField = Str::afterLast($otherField, $this->filtersConfig['date_field_prefix']['delimiter']);
                    }
                    $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
                } else {
                    $relationThrough = $relation->getRelated()->{$otherField[0]}();
                    $relationThroughOtherTable = $relationThrough->getRelated()->getTable();

                    $this->queryBuilder
                        ->leftJoin($relationThroughOtherTable, $relationThrough->getQualifiedForeignKeyName(), '=', $relationThrough->getQualifiedOwnerKeyName())
                        ->orderBy($relationThroughOtherTable.'.'.$otherField[1], $direction);
                }
            }

            return;
        }

        if ($relation instanceof BelongsToMany) {
            $this->queryBuilder
                ->leftJoin($relation->getTable(), $relation->getQualifiedForeignPivotKeyName(), '=', $relation->getQualifiedParentKeyName())
                ->leftJoin($otherTable, $relation->getQualifiedRelatedPivotKeyName(), '=', $otherTable.'.'.$relation->getRelated()->getKeyName())
                ->select($table.'.*')
                ->distinct();

            foreach ($this->relations[$field] as $otherField) {
                $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
            }

            return;
        }

        if ($relation instanceof HasMany) {
            $this->queryBuilder
                ->orderBy(DB::raw('(SELECT COUNT(*) FROM `'.$otherTable.'` WHERE '.$relation->getQualifiedForeignKeyName().' = '.$relation->getQualifiedParentKeyName().')'), $direction);

            return;
        }

        if ($relation instanceof HasOne) {
            foreach ($this->relations[$field] as $otherField) {
                $this->queryBuilder->orderBy($otherTable.'.'.$otherField, $direction);
            }
        }
    }

    /**
     * Searches the collection
     */
    private function search(): bool
    {
        if (empty($terms = array_filter(array_map('trim', explode(' ', $this->options['search']['value']))))) {
            return false;
        }

        if (! method_exists($this->model, 'scopeSearch')) {
            abort(400, 'Method scopeSearch is not set in '.get_class($this->model));
        }

        foreach ($terms as $term) {
            $this->queryBuilder->where(fn ($query) => $query->search($term));
        }

        return true;
    }

    /**
     * Applies search by column
     */
    private function searchByColumn(): bool
    {
        $table = $this->model->getTable();
        $result = false;

        foreach ($this->options['columns'] as $col) {
            $searchValue = trim($col['search']['value']);
            if ((empty($searchValue) && $searchValue !== '0') || (! empty($searchValue) && $searchValue === $this->filtersConfig['date_delimiter'])) {
                continue;
            }

            $result = true;
            $field = $col['data'];

            $this->queryBuilder->where(function ($query) use ($table, $field, $searchValue) {
                // field exists on model
                if (! isset($this->relations[$field])) {
                    if (! empty($searchType = $this->columnSearchType($searchValue))) {
                        $this->applyColumnSearch($searchType, $query, $table, $field, $searchValue);
                    } elseif (Schema::hasTable($table) && Schema::getColumnType($table, $field) == 'json') {
                        $query->where(DB::raw('LOWER(JSON_EXTRACT('.$table.'.'.$field.', "$.*"))'), 'LIKE', '%'.strtolower($searchValue).'%');
                    } else {
                        $query->where($table.'.'.$field, 'LIKE', '%'.$searchValue.'%');
                    }

                    return;
                }

                // field is relation of model
                $relation = $this->model->{$field}();
                $otherTable = $relation->getRelated()->getTable();
                $terms = array_filter(array_map('trim', explode(' ', $searchValue)));

                if ($relation instanceof MorphTo) {
                    if (! empty($terms)) {
                        $query->where(function ($query) use ($field, $terms) {
                            foreach ($this->relations[$field] as $otherField) {
                                $query->orWhereHasMorph($field, $otherField['models'], function ($query) use ($otherField, $terms) {
                                    foreach ($terms as $term) {
                                        $query->whereAny($otherField['fields'], 'LIKE', '%'.$term.'%');
                                    }
                                });
                            }
                        });
                    }

                    return;
                }

                if (Str::contains($searchValue, $this->filtersConfig['null_delimiter'])) {
                    $query->whereDoesntHave($field);

                    return;
                }

                $query->whereHas($field, function ($query) use ($field, $searchValue, $otherTable, $terms) {
                    $query->where(function ($query) use ($field, $searchValue, $otherTable, $terms) {
                        foreach ($this->relations[$field] as $otherField) {
                            if (is_string($otherField)) {
                                if (! empty($searchType = $this->columnSearchType($searchValue))) {
                                    $this->applyColumnSearch($searchType, $query, $otherTable, $otherField, $searchValue);

                                    continue;
                                }

                                if ($this->isDateFieldWithPrefix($otherField)) {
                                    $date_field_prefix_array = explode($this->filtersConfig['date_field_prefix']['delimiter'], $otherField);
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
                                        : 'DATE_FORMAT(`'.$otherTable.'`.`'.$otherField.'`, "'.$dateFormat.'")';
                                    $query->orWhere(DB::raw($dateExpr), 'LIKE', '%'.$searchValue.'%');

                                    continue;
                                }

                                if ($this->isColumnSearchWithMultipleTerms($this->relations[$field], $terms)) {
                                    foreach ($terms as $term) {
                                        $query->whereAny($this->relations[$field], 'LIKE', '%'.$term.'%');
                                    }

                                    return;
                                }

                                $query->orWhere($otherTable.'.'.$otherField, 'LIKE', '%'.$searchValue.'%');

                                continue;
                            }

                            $query->whereHas($otherField[0], function ($query) use ($otherField, $searchValue, $terms) {
                                $query->when(is_string($otherField[1]), fn ($query) => $query->where($otherField[1], 'LIKE', '%'.$searchValue.'%'));

                                if (is_array($otherField[1])) {
                                    if ($this->isColumnSearchWithMultipleTerms($otherField[1], $terms)) {
                                        foreach ($terms as $term) {
                                            $query->whereAny($otherField[1], 'LIKE', '%'.$term.'%');
                                        }

                                        return;
                                    }

                                    $query->whereAny($otherField[1], 'LIKE', '%'.$searchValue.'%');
                                }
                            });
                        }
                    });
                });
            });
        }

        return $result;
    }

    /**
     * Resolves the type of search for the given value in a column
     */
    private function columnSearchType(string $searchValue): ?string
    {
        if (Str::contains($searchValue, $this->filtersConfig['date_delimiter'])) {
            return 'date';
        }
        if (Str::contains($searchValue, $this->filtersConfig['null_delimiter'])) {
            return 'empty';
        }
        if (Str::startsWith($searchValue, '|') && Str::endsWith($searchValue, '|')) {
            return 'exact';
        }

        return null;
    }

    /**
     * Applies the column search based on the given type
     */
    private function applyColumnSearch(string $searchType, Builder $query, string $table, string $field, string $searchValue): self
    {
        if ($searchType === 'date' && count($dates = explode($this->filtersConfig['date_delimiter'], $searchValue)) > 0) {
            foreach ([['date' => $dates[0] ?? null, 'operator' => '>='], ['date' => $dates[1] ?? null, 'operator' => '<=']] as $whereData) {
                if (! empty($whereData['date'])) {
                    $query->where(DB::raw('DATE(`'.$table.'`.`'.$field.'`)'), $whereData['operator'], Carbon::createFromFormat($this->filtersConfig['date_format'], $whereData['date'])->toDateString());
                }
            }
        }

        if ($searchType === 'empty') {
            $query->where(fn ($query) => $query->where($table.'.'.$field, '')->orWhereNull($table.'.'.$field));
        }

        if ($searchType === 'exact') {
            $query->where($table.'.'.$field, trim($searchValue, '|'));
        }

        return $this;
    }

    /**
     * Checks if the given field is a date field with a prefix
     */
    private function isDateFieldWithPrefix(string $field): bool
    {
        return Str::startsWith($field, implode($this->filtersConfig['date_field_prefix']));
    }

    /**
     * Checks if search with multiple terms should be applied
     */
    private function isColumnSearchWithMultipleTerms(array $fields, array $terms): bool
    {
        return count($terms) > 1 && count($fields) === count(Arr::where($fields, fn (string $field) => ! $this->isDateFieldWithPrefix($field)));
    }

    /**
     * Formats the data for JSON response
     */
    private function getFormatedData(): array
    {
        $take = (int) $this->options['length'];
        if ($take !== -1) {
            $this->queryBuilder
                ->take($take)
                ->when(isset($this->options['start']), fn ($query) => $query->skip((int) $this->options['start']));
        }

        return [
            'draw' => (int) ($this->options['draw'] ?? 1),
            'recordsTotal' => $this->totalCount,
            'recordsFiltered' => $this->filteredCount,
            'data' => $this->queryBuilder->get()->map(fn ($item) => $item->getDatatablesData()),
        ];
    }
}
