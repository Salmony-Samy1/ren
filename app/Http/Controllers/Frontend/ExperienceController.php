<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreExperienceRequest;
use App\Http\Resources\ExperienceResource;
use App\Models\Experience;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ExperienceController extends Controller
{
    /**
     * Display a listing of the authenticated user's experiences.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $experiences = Experience::where('user_id', $user->id)
            ->with('media') // تحميل الصور بكفاءة
            ->latest()
            ->paginate(15);

        return ExperienceResource::collection($experiences);
    }

    public function store(StoreExperienceRequest $request)
    {
        $validatedData = $request->validated();
        $user = $request->user();

        try {
            DB::beginTransaction();

            $experience = Experience::create([
                'user_id' => $user->id,
                'main_service_id' => $validatedData['main_service_id'],
                'caption' => $validatedData['caption'] ?? null,
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $imageFile) {
                    $experience->addMedia($imageFile)->toMediaCollection('experience_images');
                }
            }

            DB::commit();

            return format_response(true, 'Experience created successfully', $experience->load('media'));

        } catch (\Exception $e) {
            DB::rollBack();
            \Log::error('Experience creation failed: ' . $e->getMessage());
            return format_response(false, 'Failed to create experience', code: 500);
        }
    }

    public function update(Request $request, Experience $experience)
    {
        $this->authorize('update', $experience);
        $validatedData = $request->validate([
            'caption' => 'sometimes|nullable|string|max:2000',
            'is_public' => 'sometimes|boolean',
        ]);
        $experience->update($validatedData);
        return format_response(
            true,
            'Experience updated successfully',
            new ExperienceResource($experience)
        );
    }

        public function publicIndex(Request $request)
    {
        $query = Experience::where('is_public', true)
            ->with(['user', 'media']); 

        if ($request->filled('main_service_id')) {
            $query->where('main_service_id', (int)$request->query('main_service_id'));
        }

        $experiences = $query->latest()->paginate(20);

        return ExperienceResource::collection($experiences);
    }


    public function destroy(Experience $experience)
    {
        $this->authorize('delete', $experience);
        $experience->delete();
        return format_response(true, 'Experience deleted successfully');
    }
}