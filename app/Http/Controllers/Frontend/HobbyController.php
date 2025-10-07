<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Hobby;

class HobbyController extends Controller
{
    public function index()
    {
        return format_response(true, __('Hobbies fetched'), Hobby::orderBy('name')->paginate(50));
    }
}


