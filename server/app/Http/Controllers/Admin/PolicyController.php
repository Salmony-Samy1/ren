<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Policy;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class PolicyController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $query = Policy::query();

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%")
                  ->orWhere('content', 'like', "%{$search}%");
            });
        }

        if ($category = $request->query('category')) {
            $query->where('category', $category);
        }

        $policies = $query->orderByDesc('id')->get();
        return response()->json([
            'success' => true,
            'data' => $policies,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'category' => 'required|string|max:255',
            'department' => 'required|string|max:255',
            'content' => 'required|string',
            'priority' => 'nullable|in:low,medium,high',
            'tags' => 'nullable', // array or comma-separated string
            'effective_date' => 'nullable|date',
            'review_date' => 'nullable|date',
        ]);

        // Normalize tags to array
        $tags = $request->input('tags');
        if (is_string($tags)) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
        } elseif (is_array($tags)) {
            $tags = array_values(array_filter(array_map('trim', $tags)));
        } else {
            $tags = [];
        }

        $policy = new Policy();
        $policy->title = $validated['title'];
        $policy->description = $validated['description'];
        $policy->category = $validated['category'];
        $policy->department = $validated['department'];
        $policy->content = $validated['content'];
        $policy->priority = $request->input('priority', 'medium');
        $policy->tags = $tags;
        $policy->effective_date = $request->input('effective_date');
        $policy->review_date = $request->input('review_date');
        $policy->version = '1.0';
        $policy->last_updated = now();
        $policy->status = 'draft';
        $policy->author = auth()->user()->name ?? 'system';
        $policy->save();

        return response()->json([
            'success' => true,
            'message' => 'Policy created',
            'data' => $policy,
        ], 201);
    }

    public function show(Policy $policy): JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $policy,
        ]);
    }

    public function update(Request $request, Policy $policy): JsonResponse
    {
        $validated = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|required|string',
            'category' => 'sometimes|required|string|max:255',
            'department' => 'sometimes|required|string|max:255',
            'content' => 'sometimes|required|string',
            'priority' => 'nullable|in:low,medium,high',
            'tags' => 'nullable',
            'effective_date' => 'nullable|date',
            'review_date' => 'nullable|date',
            'status' => 'nullable|in:draft,active,inactive',
            'version' => 'nullable|string|max:20',
        ]);

        $policy->fill($validated);

        if ($request->has('tags')) {
            $tags = $request->input('tags');
            if (is_string($tags)) {
                $tags = array_values(array_filter(array_map('trim', explode(',', $tags))));
            } elseif (is_array($tags)) {
                $tags = array_values(array_filter(array_map('trim', $tags)));
            } else {
                $tags = [];
            }
            $policy->tags = $tags;
        }

        $policy->last_updated = now();
        $policy->save();

        return response()->json([
            'success' => true,
            'message' => 'Policy updated',
            'data' => $policy,
        ]);
    }

    public function destroy(Policy $policy): JsonResponse
    {
        $policy->delete();
        return response()->json([
            'success' => true,
            'message' => 'Policy deleted',
        ]);
    }
}
