<?php

namespace App\Repositories\ServiceRepo;

use App\Models\Service;
use App\Repositories\BaseRepo;

class ServiceRepo extends BaseRepo implements IServiceRepo
{
    public function __construct()
    {
        $this->model = Service::class;
    }

    public function getByLocation(
        $latitude,
        $longitude,
        $radius,
        $sort = 'id',
        $direction = 'asc',
        $columns = ['*'],
        $paginated = false,
        $filter = null,
        $relations = null,
        $query_builder = false,
        $withTrashed = false,
        $perPage = 20,
    )
    {
        try {
            $builder = $this->getAll($sort, $direction, $columns, filter: $filter, relations: $relations, query_builder: true, withTrashed: $withTrashed);
            $builder->selectRaw("
        (6371 * acos(
            cos(radians(?)) *
            cos(radians(latitude)) *
            cos(radians(longitude) - radians(?)) +
            sin(radians(?)) *
            sin(radians(latitude))
        )) AS distance
    ", [$latitude, $longitude, $latitude])
                ->having('distance', '<', $radius)
                ->orderBy('distance');

            if ($paginated) {
                return $builder->paginate($perPage);
            } elseif ($query_builder) {
                return $builder;
            }

            return $builder->get();
        } catch (\Throwable $throwable) {
            report($throwable);
            return false;
        }
    }
}
