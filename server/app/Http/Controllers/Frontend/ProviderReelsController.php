<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\ProviderReel;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Requests\ProviderReelRequest;
use App\Http\Resources\ProviderReelResource;

class ProviderReelsController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->user();
        $reels = ProviderReel::where('user_id', $user->id)->latest()->paginate(20);
        
        return ProviderReelResource::collection($reels);
    }

    public function store(ProviderReelRequest $request)
    {
        $user = $request->user();

        $reel = DB::transaction(function () use ($request, $user) {
            $reel = ProviderReel::create([
                'user_id' => $user->id,
                'main_service_id' => (int)$request->input('main_service_id'),
                'title' => $request->input('title'),
                'caption' => $request->input('caption'),
                'is_public' => $request->boolean('is_public', true),
            ]);

            $reel->addMediaFromRequest('video')->toMediaCollection('reel_videos');
            
            if ($request->hasFile('thumbnail')) {
                $reel->addMediaFromRequest('thumbnail')->toMediaCollection('reel_thumbnails');
            }

            return $reel;
        });

        return format_response(true, 'Created', new ProviderReelResource($reel));
    }

    public function destroy(ProviderReel $reel)
    {
        $this->authorize('delete', $reel);
        $reel->delete();
        return format_response(true, 'Deleted');
    }

    public function publicLatest(Request $request)
    {
        $query = ProviderReel::with(['user', 'user.companyProfile'])
            ->where('is_public', true);
        if ($request->filled('main_service_id')) {
            $query->where('main_service_id', (int)$request->query('main_service_id'));
        }
        $reels = $query->latest()->paginate(min((int)$request->query('per_page', 20), 50));
        return ProviderReelResource::collection($reels);
    }
}