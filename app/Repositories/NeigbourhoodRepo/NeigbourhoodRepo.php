<?php

namespace App\Repositories\NeigbourhoodRepo;

use App\Models\Neigbourhood;
use App\Repositories\BaseRepo;

class NeigbourhoodRepo extends BaseRepo implements INeigbourhoodRepo
{
    public function __construct()
    {
        $this->model = Neigbourhood::class;
    }
}