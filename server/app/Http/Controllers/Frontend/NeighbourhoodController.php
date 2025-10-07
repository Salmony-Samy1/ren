<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\NeigbourhoodCollection;
use App\Repositories\NeigbourhoodRepo\INeigbourhoodRepo;

class NeighbourhoodController extends Controller
{
    public function __construct(private readonly INeigbourhoodRepo $repo)
    {
    }

    public function index()
    {
        return new NeigbourhoodCollection($this->repo->getAll());
    }
}
