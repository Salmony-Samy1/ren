<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Models\User;
use App\Repositories\UserRepo\IUserRepo;

class CustomersController extends Controller
{
    public function __construct(private readonly IUserRepo $userRepo)
    {
    }

    public function index()
    {
        $customers = $this->userRepo->getAll(paginated: true, filter: ['type' => 'customer'], withTrashed: true);
        if ($customers) {
            return new UserCollection($customers);
        }
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    public function show(User $customer)
    {
        return format_response(true, __('Fetched successfully'), new UserResource($customer));
    }
}
