<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Hobby;
use Illuminate\Http\Request;

class HobbyController extends Controller
{
    public function index()
    {
        return format_response(true, __('Hobbies fetched'), Hobby::orderBy('name')->paginate(50));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:hobbies,name']);
        $h = Hobby::create($data);
        return format_response(true, __('Hobby created'), $h);
    }

    public function update(Request $request, Hobby $hobby)
    {
        $data = $request->validate(['name' => 'required|string|max:255|unique:hobbies,name,'.$hobby->id]);
        $hobby->update($data);
        return format_response(true, __('Hobby updated'), $hobby);
    }

    public function destroy(Hobby $hobby)
    {
        $hobby->delete();
        return format_response(true, __('Hobby deleted'));
    }
}

