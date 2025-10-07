<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Resources\MainServiceResource;
use App\Models\MainService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class MainServiceController extends Controller
{
    public function index()
    {
        $mainServices = MainService::with('categories.translations')->get();

        $cacheKey = 'main_services_index';
        $cached = Cache::remember($cacheKey, 600, function () use ($mainServices) {
            $maxUpdated = optional($mainServices->max('updated_at'));
            return [
                'payload' => ['data' => MainServiceResource::collection($mainServices)->toArray(request())], // <-- التعديل هنا
                'lastModifiedTs' => $maxUpdated?->timestamp ?? now()->timestamp,
            ];
        });

        $etag = \App\Support\HttpCache::makeEtag(['main_services_index', $cached['lastModifiedTs']]);
        $lastModified = \Carbon\Carbon::createFromTimestamp($cached['lastModifiedTs']);
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, $lastModified->copy(), 'public', 1200, 300)) {
            return $resp304->header('Vary', 'Accept-Encoding');
        }

        return \App\Support\HttpCache::withValidators(
            response()->json($cached['payload']),
            $etag,
            $lastModified->copy(),
            300,
            'public',
            1200
        )->header('Vary', 'Accept-Encoding');
    }

public function show(MainService $mainService)
    {
        $mainService->load('categories.translations');

        $etag = \App\Support\HttpCache::makeEtag(['main_services_show', $mainService->id, $mainService->updated_at?->timestamp]);
        $lastModified = $mainService->updated_at ?? $mainService->created_at;
        if ($resp304 = \App\Support\HttpCache::preconditionCheck(request(), $etag, optional($lastModified)->copy(), 'public', 600, 300)) {
            return $resp304->header('Vary', 'Accept-Encoding');
        }
        
        // التعديل في السطر التالي فقط
        return \App\Support\HttpCache::withValidators(
            (new MainServiceResource($mainService))->toResponse(request()), // <-- هذا هو التعديل
            $etag,
            optional($lastModified)->copy(),
            300,
            'public',
            600
        )->header('Vary', 'Accept-Encoding');
    }

    /**
     * رفع صورة للخدمة الرئيسية
     */
    public function uploadImage(Request $request, MainService $mainService): JsonResponse
    {
        $request->validate([
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,webp|max:5120', // 5MB max
        ]);

        try {
            // حذف الصورة القديمة إذا كانت موجودة
            $mainService->clearMediaCollection('service_image');
            
            // رفع الصورة الجديدة
            $media = $mainService->addMediaFromRequest('image')
                ->toMediaCollection('service_image');

            // مسح الكاش
            Cache::forget('main_services_index');
            Cache::forget("main_services_show_{$mainService->id}");

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الصورة بنجاح',
                'data' => [
                    'media_id' => $media->id,
                    'image_url' => $media->getUrl(),
                    'image_thumb_url' => $media->getUrl('thumb'),
                    'file_name' => $media->file_name,
                    'file_size' => $media->size,
                    'mime_type' => $media->mime_type,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الصورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * رفع فيديو للخدمة الرئيسية
     */
    public function uploadVideo(Request $request, MainService $mainService): JsonResponse
    {
        $request->validate([
            'video' => 'required|file|mimes:mp4,avi,mov,wmv,flv,webm|max:51200', // 50MB max
        ]);

        try {
            // حذف الفيديو القديم إذا كان موجوداً
            $mainService->clearMediaCollection('service_video');
            
            // رفع الفيديو الجديد
            $media = $mainService->addMediaFromRequest('video')
                ->toMediaCollection('service_video');

            // مسح الكاش
            Cache::forget('main_services_index');
            Cache::forget("main_services_show_{$mainService->id}");

            return response()->json([
                'success' => true,
                'message' => 'تم رفع الفيديو بنجاح',
                'data' => [
                    'media_id' => $media->id,
                    'video_url' => $media->getUrl(),
                    'file_name' => $media->file_name,
                    'file_size' => $media->size,
                    'mime_type' => $media->mime_type,
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء رفع الفيديو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف صورة من الخدمة الرئيسية
     */
    public function deleteImage(MainService $mainService, $mediaId): JsonResponse
    {
        try {
            $media = $mainService->getMedia('service_image')->where('id', $mediaId)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'الصورة غير موجودة'
                ], 404);
            }

            $media->delete();

            // مسح الكاش
            Cache::forget('main_services_index');
            Cache::forget("main_services_show_{$mainService->id}");

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الصورة بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الصورة',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * حذف فيديو من الخدمة الرئيسية
     */
    public function deleteVideo(MainService $mainService, $mediaId): JsonResponse
    {
        try {
            $media = $mainService->getMedia('service_video')->where('id', $mediaId)->first();
            
            if (!$media) {
                return response()->json([
                    'success' => false,
                    'message' => 'الفيديو غير موجود'
                ], 404);
            }

            $media->delete();

            // مسح الكاش
            Cache::forget('main_services_index');
            Cache::forget("main_services_show_{$mainService->id}");

            return response()->json([
                'success' => true,
                'message' => 'تم حذف الفيديو بنجاح'
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء حذف الفيديو',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * جلب جميع صور الخدمة الرئيسية
     */
    public function getImages(MainService $mainService): JsonResponse
    {
        try {
            $images = $mainService->getMedia('service_image');
            $videos = $mainService->getMedia('service_video');

            return response()->json([
                'success' => true,
                'message' => 'تم جلب الملفات بنجاح',
                'data' => [
                    'images' => $images->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                            'thumb_url' => $media->getUrl('thumb'),
                            'file_name' => $media->file_name,
                            'file_size' => $media->size,
                            'mime_type' => $media->mime_type,
                            'created_at' => $media->created_at,
                        ];
                    }),
                    'videos' => $videos->map(function ($media) {
                        return [
                            'id' => $media->id,
                            'url' => $media->getUrl(),
                            'file_name' => $media->file_name,
                            'file_size' => $media->size,
                            'mime_type' => $media->mime_type,
                            'created_at' => $media->created_at,
                        ];
                    }),
                    'total_images' => $images->count(),
                    'total_videos' => $videos->count(),
                ]
            ]);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'حدث خطأ أثناء جلب الملفات',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}