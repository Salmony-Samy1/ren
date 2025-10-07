<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Hobby;

class HobbiesController extends Controller
{
    public function index()
    {
        return format_response(true, __('Hobbies fetched'), Hobby::orderBy('name')->get(['id','name']));
    }
}

