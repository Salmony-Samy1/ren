<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Traits\AdminAuthTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class UserContentReviewController extends Controller
{
    use AdminAuthTrait;

    public function __construct()
    {
        $this->middleware(['auth:api', 'user_type:admin', 'throttle:admin']);
    }

    /**
     * جلب المحتوى للمراجعة - وفقاً للتوثيق
     * GET /api/v1/admin/user-content/reviews
     */
    public function index(Request $request)
    {
        if (!$this->validateAdminAccess()) {
            return $this->forbiddenResponse('مراجعة محتوى المستخدمين');
        }

        try {
            // فلترة البيانات بناءً على الطلب (للمحاكاة)
            $filters = [
                'status' => $request->get('status'),
                'event_id' => $request->get('event_id'),
                'content_type' => $request->get('content_type'), // photo, video, review
                'date_from' => $request->get('date_from'),
                'date_to' => $request->get('date_to'),
                'search' => $request->get('search'),
            ];

            // بيانات وهمية متنوعة لمراجعة المحتوى
            $mockContentItems = [
                [
                    'content_id' => 1,
                    'event_id' => 1,
                    'event_title' => 'مؤتمر التكنولوجيا 2024',
                    'user_id' => 1,
                    'user_name' => 'أحمد محمد',
                    'user_email' => 'ahmed@example.com',
                    'user_avatar' => null,
                    'content_type' => 'photo',
                    'status' => 'pending',
                    'content_data' => [
                        'image_url' =>'https://cdn.gathro.net/user-uploads/event-photos/photo_001.jpg',
                        'caption' => 'لقطة رائعة من المؤتمر',
                        'tags' => ['مؤتمر', 'تكنولوجيا', 'ابتكار']
                    ],
                    'created_at' => '2025-10-03T10:30:42.217074Z',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'moderation_notes' => null,
                    'report_count' => 0,
                    'likes_count' => 8,
                    'comments_count' => 3
                ],
                [
                    'content_id' => 2,
                    'event_id' => 2,
                    'event_title' => 'ملتقى الابتكار الأخضر',
                    'user_id' => 2,
                    'user_name' => 'فاطمة أحمد',
                    'user_email' => 'fatima@example.com',
                    'user_avatar' => null,
                    'content_type' => 'photo',
                    'status' => 'pending',
                    'content_data' => [
                        'image_url' =>'https://cdn.gathro.net/user-uploads/event-photos/photo_yy.png',
                        'caption' => 'وجهات نظر جديدة حول الاستدامة',
                        'tags' => ['اجتماع', 'بيئة', 'استدامة']
                    ],
                    'created_at' => '2025-10-04T10:30:42.217074Z',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'moderation_notes' => null,
                    'report_count' => 0,
                    'likes_count' => 12,
                    'comments_count' => 5
                ],
                [
                    'content_id' => 3,
                    'event_id' => 3,
                    'event_title' => 'سيمبوسيوم الطب الرقمي',
                    'user_id' => 3,
                    'user_name' => 'خالد سالم',
                    'user_email' => 'khalid@example.com',
                    'user_avatar' => null,
                    'content_type' => 'video',
                    'status' => 'pending',
                    'content_data' => [
                        'video_url' =>'https://cdn.gathro.net/user-uploads/event-videos/video_001.mp4',
                        'thumbnail_url' =>'https://cdn.gathro.net/user-uploads/event-videos/thumb_001.jpg',
                        'duration' => 120,
                        'caption' => 'نظرة عامة حول مستقبل الطب الرقمي',
                        'tags' => ['طب', 'رقمي', 'صحة']
                    ],
                    'created_at' => '2025-10-05T14:20:42.217074Z',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'moderation_notes' => null,
                    'report_count' => 0,
                    'likes_count' => 15,
                    'comments_count' => 7
                ],
                [
                    'content_id' => 4,
                    'event_id' => 1,
                    'event_title' => 'مؤتمر التكنولوجيا 2024',
                    'user_id' => 4,
                    'user_name' => 'نور الدين',
                    'user_email' => 'nour@example.com',
                    'user_avatar' => null,
                    'content_type' => 'review',
                    'status' => 'approved',
                    'content_data' => [
                        'rating' => 4.5,
                        'title' => 'تجربة رائعة',
                        'comment' => 'لقد كان هذا المؤتمر مفيداً جداً وتم تنظيمه بشكل احترافي',
                        'tags' => ['مراجعة', 'رضا', 'مفيد']
                    ],
                    'created_at' => '2025-10-04T16:45:42.217074Z',
                    'reviewed_at' => '2025-10-04T17:20:42.217074Z',
                    'reviewed_by' => 'admin@gathro.net',
                    'moderation_notes' => 'مراجعة إيجابية ومفيدة للمنصة',
                    'report_count' => 0,
                    'likes_count' => 21,
                    'comments_count' => 2
                ],
                [
                    'content_id' => 5,
                    'event_id' => 2,
                    'event_title' => 'ملتقى الابتكار الأخضر',
                    'user_id' => 5,
                    'user_name' => 'سارة عمر',
                    'user_email' => 'sara@example.com',
                    'user_avatar' => null,
                    'content_type' => 'photo',
                    'status' => 'rejected',
                    'content_data' => [
                        'image_url' =>'https://cdn.gathro.net/user-uploads/event-photos/photo_unsub.png',
                        'caption' => 'صورة غير مناسبة',
                        'tags' => ['محتوى', 'معلن']
                    ],
                    'created_at' => '2025-10-04T20:15:42.217074Z',
                    'reviewed_at' => '2025-10-05T09:30:42.217074Z',
                    'reviewed_by' => 'moderator@gathro.net',
                    'moderation_notes' => 'انتهاك سياسة المحتوى - يحتوي على محتوى إعلاني',
                    'report_count' => 2,
                    'likes_count' => 1,
                    'comments_count' => 0
                ],
                [
                    'content_id' => 6,
                    'event_id' => 4,
                    'event_title' => 'ورش العمل التطبيقية',
                    'user_id' => 6,
                    'user_name' => 'محمد علي',
                    'user_email' => 'mohammed.ali@example.com',
                    'user_avatar' => null,
                    'content_type' => 'photo',
                    'status' => 'pending',
                    'content_data' => [
                        'image_url' =>'https://cdn.gathro.net/user-uploads/event-photos/photo_04.png',
                        'caption' => 'ورش العمل التفاعلية كانت ممتازة',
                        'tags' => ['ورش', 'عمل', 'تفاعلي']
                    ],
                    'created_at' => '2025-10-06T11:20:42.217074Z',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'moderation_notes' => null,
                    'report_count' => 0,
                    'likes_count' => 3,
                    'comments_count' => 1
                ],
                [
                    'content_id' => 7,
                    'event_id' => 5,
                    'event_title' => 'ندوة الفضاء والاستكشاف',
                    'user_id' => 7,
                    'user_name' => 'عائشة حسن',
                    'user_email' => 'aisha@example.com',
                    'user_avatar' => null,
                    'content_type' => 'review',
                    'status' => 'approved',
                    'content_data' => [
                        'rating' => 5.0,
                        'title' => 'تجربة لا تُنسى',
                        'comment' => 'هذه الندوة غيرت مفهومي عن الفضاء تماماً. محتوى عالي الجودة ومتكلمون خبراء',
                        'tags' => ['فضاء', 'استكشاف', 'مفيد']
                    ],
                    'created_at' => '2025-10-06T15:40:42.217074Z',
                    'reviewed_at' => '2025-10-06T16:15:42.217074Z',
                    'reviewed_by' => 'admin@gathro.net',
                    'moderation_notes' => 'مراجعة ممتازة ومفصلة',
                    'report_count' => 0,
                    'likes_count' => 28,
                    'comments_count' => 4
                ],
                [
                    'content_id' => 8,
                    'event_id' => 3,
                    'event_title' => 'سيمبوسيوم الطب الرقمي',
                    'user_id' => 8,
                    'user_name' => 'عبد الرحمن وديع',
                    'user_email' => 'abdurrahman@example.com',
                    'user_avatar' => null,
                    'content_type' => 'video',
                    'status' => 'pending',
                    'content_data' => [
                        'video_url' =>'https://cdn.gathro.net/user-uploads/event-videos/video_002.mp4',
                        'thumbnail_url' =>'https://cdn.gathro.net/user-uploads/event-videos/thumb_002.jpg',
                        'duration' => 180,
                        'caption' => 'تسجيل لحظات مهمة من العرض',
                        'tags' => ['طب', 'رقمي', 'تسجيل']
                    ],
                    'created_at' => '2025-10-07T09:50:42.217074Z',
                    'reviewed_at' => null,
                    'reviewed_by' => null,
                    'moderation_notes' => null,
                    'report_count' => 1,
                    'likes_count' => 6,
                    'comments_count' => 2
                ]
            ];

            // تطبيق الفلاتر
            $filteredContent = collect($mockContentItems);

            if ($filters['status']) {
                $filteredContent = $filteredContent->where('status', $filters['status']);
            }

            if ($filters['event_id']) {
                $filteredContent = $filteredContent->where('event_id', $filters['event_id']);
            }

            if ($filters['content_type']) {
                $filteredContent = $filteredContent->where('content_type', $filters['content_type']);
            }

            // تطبيق البحث
            if ($filters['search']) {
                $searchTerm = strtolower($filters['search']);
                $filteredContent = $filteredContent->filter(function ($content) use ($searchTerm) {
                    return strpos(strtolower($content['user_name']), $searchTerm) !== false ||
                           strpos(strtolower($content['user_email']), $searchTerm) !== false ||
                           strpos(strtolower($content['event_title']), $searchTerm) !== false;
                });
            }

            // تقسيم الصفحات (محاكاة)
            $perPage = 20;
            $currentPage = $request->get('page', 1);
            $total = $filteredContent->count();
            $contentItems = $filteredContent->forPage($currentPage, $perPage)->values();

            // حساب الإحصائيات
            $stats = [
                'total' => $total,
                'pending' => $filteredContent->where('status', 'pending')->count(),
                'approved' => $filteredContent->where('status', 'approved')->count(),
                'rejected' => $filteredContent->where('status', 'rejected')->count(),
            ];

            return format_response(true, 'تم جلب المحتوى للمراجعة بنجاح', [
                'content_items' => $contentItems,
                'pagination' => [
                    'current_page' => $currentPage,
                    'per_page' => $perPage,
                    'total' => $total,
                    'last_page' => ceil($total / $perPage),
                    'has_next' => $currentPage < ceil($total / $perPage),
                    'has_prev' => $currentPage > 1,
                ],
                'statistics' => $stats,
                'filters_applied' => array_filter($filters, fn($value) => !empty($value)),
            ]);

        } catch (\Exception $e) {
            return $this->handleAdminException($e, 'جلب محتوى المستخدمين للمراجعة');
        }
    }

    /**
     * تحديث حالة المحتوى - وفقاً للتوثيق
     * PUT /api/v1/admin/user-content/reviews/{content}/status
     */
    public function updateStatus(Request $request, $contentId)
    {
        if (!$this->validateAdminAccess()) {
            return $this->forbiddenResponse('تحديث حالة المحتوى');
        }

        try {
            $validated = $request->validate([
                'status' => 'required|in:approved,rejected,pending',
                'moderation_notes' => 'nullable|string|max:500',
                'auto_notify_user' => 'boolean'
            ]);

            // محاكاة تحديث الحالة
            $mockUpdatedContent = [
                'content_id' => $contentId,
                'event_id' => 1,
                'event_title' => 'مؤتمر التكنولوجيا 2024',
                'user_id' => 1,
                'user_name' => 'أحمد محمد',
                'user_email' => 'ahmed@example.com',
                'content_type' => 'photo',
                'status' => $validated['status'],
                'reviewed_at' => now()->toISOString(),
                'reviewed_by' => auth()->user()->email,
                'moderation_notes' => $validated['moderation_notes'] ?? null,
            ];

            return format_response(true, 'تم تحديث حالة المحتوى بنجاح', [
                'content' => $mockUpdatedContent,
                'changes' => $validated,
                'moderation_log' => [
                    'updated_by' => auth()->user()->full_name,
                    'updated_at' => now()->toISOString(),
                    'previously_reviewed' => $validated['status'] !== 'pending',
                ],
            ]);

        } catch (\Exception $e) {
            return $this->handleAdminException($e, 'تحديث حالة المحتوى', [
                'content_id' => $contentId,
                'new_status' => $request->get('status')
            ]);
        }
    }
}
