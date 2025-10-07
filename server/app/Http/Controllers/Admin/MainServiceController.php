<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Resources\MainServiceResource;
use App\Models\MainService;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Validation\Rule;

class MainServiceController extends Controller
{
    public function index()
    {
        return MainServiceResource::collection(MainService::all());
    }


    public function store(Request $request)
    {
        $validatedData = $request->validate([
            'name'           => 'required|string|max:255|unique:main_services,name',
            'description'    => 'nullable|string',
            'name_en'        => 'required|string|max:255|unique:main_services,name_en',
            'description_en' => 'nullable|string',
            'status'         => 'sometimes|boolean',
            'image'          => 'nullable|image|max:2048',
            'video'          => 'nullable|file|max:20480',
        ]);

        $mainService = MainService::create($validatedData);

        if ($request->hasFile('image')) {
            $mainService->addMediaFromRequest('image')->toMediaCollection('service_image');
        }
        if ($request->hasFile('video')) {
            $mainService->addMediaFromRequest('video')->toMediaCollection('service_video');
        }

        return new MainServiceResource($mainService->fresh());
    }


    public function show(MainService $mainService)
    {
        return new MainServiceResource($mainService);
    }

    public function update(Request $request, MainService $mainService)
    {
        $validatedData = $request->validate([
            'name'           => ['required', 'string', 'max:255', Rule::unique('main_services')->ignore($mainService->id)],
            'description'    => 'nullable|string',
            'name_en'        => ['required', 'string', 'max:255', Rule::unique('main_services')->ignore($mainService->id)],
            'description_en' => 'nullable|string',
            'status'         => 'sometimes|boolean',
            'image'          => 'nullable|image|max:2048',
            'video'          => 'nullable|file|max:20480',
        ]);

        $mainService->update($validatedData);

        if ($request->hasFile('image')) {
            $mainService->addMediaFromRequest('image')->toMediaCollection('service_image');
        }
        if ($request->hasFile('video')) {
            $mainService->addMediaFromRequest('video')->toMediaCollection('service_video');
        }

        return new MainServiceResource($mainService->fresh());
    }

    public function destroy(MainService $mainService)
    {
        $mainService->delete();
        return response()->json(['message' => 'Main service deleted successfully.'], 200);
    }
}