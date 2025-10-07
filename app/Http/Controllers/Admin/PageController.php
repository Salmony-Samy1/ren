<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Page;
use Illuminate\Http\Request;

class PageController extends Controller
{
    public function index()
    {
        return response()->json(Page::orderByDesc('id')->paginate(20));
    }
    public function store(Request $request)
    {
        $data = $request->validate([
            'slug' => 'required|string|max:100|unique:pages,slug',
            'title' => 'required|string|max:200',
            'content' => 'required|string',
            'status' => 'required|in:draft,published',
        ]);
        $page = Page::create($data);
        return response()->json($page, 201);
    }
    public function show(Page $page)
    {
        return response()->json($page);
    }
    public function update(Request $request, Page $page)
    {
        $data = $request->validate([
            'title' => 'sometimes|string|max:200',
            'content' => 'sometimes|string',
            'status' => 'sometimes|in:draft,published',
        ]);
        $page->update($data);
        return response()->json($page);
    }
    public function destroy(Page $page)
    {
        $page->delete();
        return response()->json(['message' => 'Deleted']);
    }
}

