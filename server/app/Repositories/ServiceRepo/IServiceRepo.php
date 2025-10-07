<?php

namespace App\Repositories\ServiceRepo;

use App\Repositories\IBaseRepo;

interface IServiceRepo extends IBaseRepo
{
    // Define Service specific methods
    public function getByLocation(
        $latitude,
        $longitude,
        $radius,
        string $sort,
        string $direction,
        array $columns,
        bool $paginated,
        ?array $filter,
        ?array $relations,
        bool $query_builder,
        bool $withTrashed,
        int $perPage,
    );
}
