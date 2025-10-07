<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

interface IBaseRepo
{
    /**
     * Get all records with optional filters and options.
     *
     * @param string $sort
     * @param string $direction
     * @param array $columns
     * @param bool $paginated
     * @param array|null $filter
     * @param array|null $relations
     * @param bool $query_builder
     * @param bool $withTrashed
     * @param int $perPage
     * @return Builder|Collection|LengthAwarePaginator|false
     */
    public function getAll(
        string $sort,
        string $direction,
        array $columns,
        bool $paginated,
        ?array $filter,
        ?array $relations,
        bool $query_builder,
        bool $withTrashed,
        int $perPage
    );

    /**
     * Get a record by its ID.
     */
    public function getById($id, $columns = ['*'], $relations = null);

    /**
     * Create a new record.
     */
    public function create(array $data);

    /**
     * Update an existing record by ID.
     */
    public function update($id, array $data);

    /**
     * Soft delete a record by ID.
     */
    public function delete($id);

    /**
     * Permanently delete a record by ID.
     */
    public function forceDelete($id);

    /**
     * Restore a soft-deleted record by ID.
     */
    public function restore($id);

    /**
     * Find or create a record.
     */
    public function firstOrCreate(array $attributes, array $values = []);

    /**
     * Update or create a record.
     */
    public function updateOrCreate(array $attributes, array $values);

    /**
     * Get all records that match given conditions.
     */
    public function getWhere(array $conditions, $columns = ['*'], $relations = null);
}
