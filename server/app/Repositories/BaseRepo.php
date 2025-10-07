<?php

namespace App\Repositories;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

abstract class BaseRepo implements IBaseRepo
{
    /**
     * @var Model
     */
    protected $model;

    /**
     * Get all records with optional filtering, sorting, and relations.
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
     * @return Builder|Collection|\Illuminate\Contracts\Pagination\LengthAwarePaginator|false
     */
    public function getAll($sort = 'id', $direction = 'asc', $columns = ['*'], $paginated = false, $filter = null, $relations = null, $query_builder = false, $withTrashed = false, $perPage = 20)
    {
        try {
            $query = $this->model::query()
                ->select($columns)
                ->orderBy($sort, $direction);

            if ($withTrashed && method_exists($this->model, 'withTrashed')) {
                $query->withTrashed();
            }

            if (!empty($relations)) {
                $query->with($relations);
            }

            if (!empty($filter) && is_array($filter)) {
                foreach ($filter as $key => $value) {
                    if (is_array($value)) {
                        if (isset($value[0], $value[1])) {
                            $operator = $value[0];
                            $val = $value[1];
                            if ($operator === 'between' && is_array($val)) {
                                $query->whereBetween($key, $val);
                            } else {
                                $query->where($key, $operator, $val);
                            }
                        }
                    } else {
                        $query->where($key, '=', $value);
                    }
                }
            }

            return $query_builder
                ? $query
                : ($paginated ? $query->paginate($perPage) : $query->get());
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function getById($id, $columns = ['*'], $relations = null)
    {
        try {
            $item = $this->model::select($columns)->find($id);
            if ($item && $relations) {
                $item->load($relations);
            }
            return $item;
        } catch (\Throwable $e) {
            report($e);
            return null;
        }
    }

    public function create(array $data)
    {
        try {
            return $this->model::create($data);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function update($id, array $data)
    {
        try {
            $record = $this->model::findOrFail($id);
            $record->update($data);
            return $record;
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function delete($id)
    {
        try {
            $record = $this->model::findOrFail($id);
            return $record->delete();
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function forceDelete($id)
    {
        try {
            $record = $this->model::withTrashed()->findOrFail($id);
            return $record->forceDelete();
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function restore($id)
    {
        try {
            $record = $this->model::withTrashed()->findOrFail($id);
            return $record->restore();
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function firstOrCreate(array $attributes, array $values = [])
    {
        try {
            return $this->model::firstOrCreate($attributes, $values);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }

    public function updateOrCreate(array $attributes, array $values)
    {
        try {
            return $this->model::updateOrCreate($attributes, $values);
        } catch (\Throwable $e) {
            report($e);
            return false;
        }
    }


    public function getWhere(array $conditions, $columns = ['*'], $relations = null)
    {
        try {
            $query = $this->model::select($columns)->where($conditions);

            if ($relations) {
                $query->with($relations);
            }

            return $query->get();
        } catch (\Throwable $e) {
            report($e);
            return collect();
        }
    }
}
