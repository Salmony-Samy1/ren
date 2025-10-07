<?php

namespace App\Repositories\CompanyProfileRepo;

use App\Models\CompanyProfile;
use App\Repositories\BaseRepo;

class CompanyProfileRepo extends BaseRepo implements ICompanyProfileRepo
{
    public function __construct()
    {
        $this->model = CompanyProfile::class;
    }
}