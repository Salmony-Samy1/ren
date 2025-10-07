<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\RegionCollection;
use App\Repositories\RegionRepo\IRegionRepo;

class RegionsController extends Controller
{
    public function __construct(private readonly IRegionRepo $repo)
    {
    }

    public function index()
    {
        return new RegionCollection($this->repo->getAll());
    }
}
