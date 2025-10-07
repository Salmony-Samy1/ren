<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\TrainingVideo;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\Storage;

class TrainingVideoController extends Controller
{
    public function index(Request $request)
    {
        $query = TrainingVideo::query();

        if ($search = $request->query('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('title', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }
        if ($dept = $request->query('department')) {
            $query->where('department', $dept);
        }

        $videos = $query->orderByDesc('created_at')->get();

        return response()->json([
            'success' => true,
            'data' => $videos,
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string',
            'department' => 'nullable|string|max:255',
            'duration' => 'nullable|string|max:20',
            'difficulty' => 'nullable|in:beginner,intermediate,advanced',
            'tags' => 'nullable', // array or comma-separated string
            'status' => 'nullable|in:active,inactive,draft',
            'video' => 'required|file|mimes:mp4,mov,avi,mkv,webm',
            'thumbnail' => 'nullable|file|mimes:jpg,jpeg,png,webp',
        ]);

        // Normalize tags
        if (isset($data['tags']) && is_string($data['tags'])) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
            $data['tags'] = $tags;
        }

        $data['uploaded_by'] = optional($request->user('api'))->id ?? optional($request->user())->id;

        // Store files
        $videoPath = null;
        $thumbPath = null;
        if ($request->hasFile('video')) {
            $videoPath = $request->file('video')->store('videos/training', 'public');
            // Ensure visibility is public
            Storage::disk('public')->setVisibility($videoPath, 'public');
        }
        if ($request->hasFile('thumbnail')) {
            $thumbPath = $request->file('thumbnail')->store('thumbnails/training', 'public');
            Storage::disk('public')->setVisibility($thumbPath, 'public');
        }

        $payload = [
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'department' => $data['department'] ?? null,
            'duration' => $data['duration'] ?? null,
            'difficulty' => $data['difficulty'] ?? 'beginner',
            'tags' => $data['tags'] ?? [],
            'status' => $data['status'] ?? 'active',
            'uploaded_by' => $data['uploaded_by'] ?? null,
            'video_path' => $videoPath,
            'thumbnail_path' => $thumbPath,
            'video_url' => null,
            'thumbnail_url' => $thumbPath ? Storage::disk('public')->url($thumbPath) : null,
        ];

        $video = TrainingVideo::create($payload);

        // Try to auto-generate thumbnail if not provided and ffmpeg is available
        if (!$thumbPath && $videoPath) {
            try {
                $generated = $this->tryGenerateThumbnail($videoPath);
                if ($generated) {
                    $video->thumbnail_path = $generated;
                    $video->thumbnail_url = Storage::disk('public')->url($generated);
                    $video->save();
                }
            } catch (\Throwable $e) {
                // ignore silently; fallback to no thumbnail
            }
        }

        return response()->json([
            'success' => true,
            'data' => $video,
        ], 201);
    }

    public function show(TrainingVideo $video)
    {
        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function update(Request $request, TrainingVideo $video)
    {
        $data = $request->validate([
            'title' => 'sometimes|required|string|max:255',
            'description' => 'sometimes|nullable|string',
            'department' => 'sometimes|nullable|string|max:255',
            'duration' => 'sometimes|nullable|string|max:20',
            'difficulty' => 'sometimes|nullable|in:beginner,intermediate,advanced',
            'tags' => 'sometimes|nullable',
            'status' => 'sometimes|nullable|in:active,inactive,draft',
            'video' => 'sometimes|file|mimes:mp4,mov,avi,mkv,webm',
            'thumbnail' => 'sometimes|file|mimes:jpg,jpeg,png,webp',
        ]);

        if (array_key_exists('tags', $data) && is_string($data['tags'])) {
            $tags = array_values(array_filter(array_map('trim', explode(',', $data['tags']))));
            $data['tags'] = $tags;
        }

        $update = $data;
        unset($update['video'], $update['thumbnail']);

        // Handle new files
        if ($request->hasFile('video')) {
            $path = $request->file('video')->store('videos/training', 'public');
            Storage::disk('public')->setVisibility($path, 'public');
            $update['video_path'] = $path;
            // Reset URL if new local file uploaded
            $update['video_url'] = null;
        }
        if ($request->hasFile('thumbnail')) {
            $path = $request->file('thumbnail')->store('thumbnails/training', 'public');
            Storage::disk('public')->setVisibility($path, 'public');
            $update['thumbnail_path'] = $path;
            $update['thumbnail_url'] = Storage::disk('public')->url($path);
        }

        $video->update($update);

        return response()->json([
            'success' => true,
            'data' => $video,
        ]);
    }

    public function destroy(TrainingVideo $video)
    {
        // Delete files if exist
        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            Storage::disk('public')->delete($video->video_path);
        }
        if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
            Storage::disk('public')->delete($video->thumbnail_path);
        }
        $video->delete();

        return response()->json([
            'success' => true,
            'message' => 'Deleted',
        ]);
    }

    public function stream(Request $request, TrainingVideo $video)
    {
        // If stored locally (public disk)
        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            $mime = Storage::disk('public')->mimeType($video->video_path) ?? 'video/mp4';
            $stream = Storage::disk('public')->readStream($video->video_path);
            return Response::stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Accept-Ranges' => 'bytes',
                'Cache-Control' => 'private, max-age=0, must-revalidate',
            ]);
        }

        // If external URL exists, redirect for streaming
        if ($video->video_url) {
            return redirect()->away($video->video_url);
        }

        return response()->json(['success' => false, 'message' => 'Video not found'], 404);
    }

    public function download(Request $request, TrainingVideo $video)
    {
        if ($video->video_path && Storage::disk('public')->exists($video->video_path)) {
            $filename = pathinfo($video->video_path, PATHINFO_BASENAME);
            return Storage::disk('public')->download($video->video_path, $filename);
        }

        if ($video->video_url) {
            // Redirect to the actual URL for download when not stored locally
            return redirect()->away($video->video_url);
        }

        return response()->json(['success' => false, 'message' => 'Video not found'], 404);
    }

    public function thumbnail(Request $request, TrainingVideo $video)
    {
        if ($video->thumbnail_path && Storage::disk('public')->exists($video->thumbnail_path)) {
            $mime = Storage::disk('public')->mimeType($video->thumbnail_path) ?? 'image/jpeg';
            $stream = Storage::disk('public')->readStream($video->thumbnail_path);
            return Response::stream(function () use ($stream) {
                fpassthru($stream);
            }, 200, [
                'Content-Type' => $mime,
                'Cache-Control' => 'public, max-age=31536000',
            ]);
        }

        if ($video->thumbnail_url) {
            return redirect()->away($video->thumbnail_url);
        }

        return response()->json(['success' => false, 'message' => 'Thumbnail not found'], 404);
    }

    private function tryGenerateThumbnail(string $publicPath): ?string
    {
        // Generate a thumbnail at 1s into the video if ffmpeg is available
        try {
            $storage = Storage::disk('public');
            $videoFull = $storage->path($publicPath);
            $thumbRel = 'thumbnails/training/'.pathinfo($publicPath, PATHINFO_FILENAME).'_thumb.jpg';
            $thumbFull = $storage->path($thumbRel);

            // Try to call ffmpeg if available
            $cmd = 'ffmpeg -y -ss 00:00:01 -i '.escapeshellarg($videoFull).' -frames:v 1 '.escapeshellarg($thumbFull).' 2>&1';
            @shell_exec($cmd);

            if (file_exists($thumbFull)) {
                return $thumbRel;
            }
        } catch (\Throwable $e) {
            // ignore
        }
        return null;
    }
}
