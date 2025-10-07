<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Http\Requests\TechnicalIssueRequest;
use App\Http\Requests\SuggestionRequest;
use App\Models\Faq;
use App\Models\Suggestion;
use App\Models\SupportTicket;
use App\Models\Conversation;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HelpSupportController extends Controller
{
    public function __construct(private readonly NotificationService $notificationService)
    {
    }

    /**
     * 1. المحادثة المباشرة مع الدعم الفني
     */
    public function startSupportChat()
    {
        $user = Auth::user();
        
        // البحث عن مستخدم الدعم الفني
        $supportUser = User::where('email', 'support@gathro.com')->first();
        
        if (!$supportUser) {
            return format_response(false, 'خدمة الدعم الفني غير متاحة حالياً', [], 503);
        }

        // البحث عن محادثة موجودة
        $conversation = Conversation::where(function ($query) use ($user, $supportUser) {
            $query->where('user1_id', $user->id)->where('user2_id', $supportUser->id);
        })->orWhere(function ($query) use ($user, $supportUser) {
            $query->where('user1_id', $supportUser->id)->where('user2_id', $user->id);
        })->first();

        if ($conversation) {
            return format_response(true, 'تم العثور على محادثة موجودة', [
                'conversation_id' => $conversation->id,
                'support_user' => [
                    'id' => $supportUser->id,
                    'name' => $supportUser->name,
                    'email' => $supportUser->email,
                ],
                'status' => 'existing'
            ]);
        }

        // إنشاء محادثة جديدة
        $newConversation = Conversation::create([
            'user1_id' => $user->id,
            'user2_id' => $supportUser->id,
        ]);

        // إرسال إشعار للدعم الفني
        $this->notificationService->created([
            'user_id' => $supportUser->id,
            'action' => 'new_support_chat',
            'message' => 'لديك محادثة دعم فني جديدة من ' . $user->name,
        ]);

        return format_response(true, 'تم بدء المحادثة مع الدعم الفني', [
            'conversation_id' => $newConversation->id,
            'support_user' => [
                'id' => $supportUser->id,
                'name' => $supportUser->name,
                'email' => $supportUser->email,
            ],
            'status' => 'new'
        ]);
    }

    /**
     * 2. قاعدة المعرفة - الأسئلة الشائعة
     */
    public function getKnowledgeBase()
    {
        $faqs = Faq::where('is_visible', true)
            ->orderBy('sort_order', 'asc')
            ->get(['id', 'question', 'answer']);

        // إضافة صفحات قانونية كجزء من قاعدة المعرفة
        $legalPages = [
            [
                'id' => 'cancellation-policy',
                'title' => 'سياسة الإلغاء',
                'description' => 'تعرف على سياسة الإلغاء والاسترداد'
            ],
            [
                'id' => 'payment-policy',
                'title' => 'سياسة الدفع',
                'description' => 'تعرف على طرق الدفع المتاحة'
            ],
            [
                'id' => 'terms-of-service',
                'title' => 'شروط الاستخدام',
                'description' => 'اقرأ شروط وأحكام استخدام المنصة'
            ],
            [
                'id' => 'privacy-policy',
                'title' => 'سياسة الخصوصية',
                'description' => 'تعرف على كيفية حماية بياناتك'
            ]
        ];

        return format_response(true, 'تم جلب قاعدة المعرفة بنجاح', [
            'faqs' => $faqs,
            'legal_pages' => $legalPages,
            'total_faqs' => $faqs->count(),
            'total_legal_pages' => count($legalPages)
        ]);
    }

    /**
     * 3. الإبلاغ عن مشكلة تقنية
     */
    public function reportTechnicalIssue(TechnicalIssueRequest $request)
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $ticket = SupportTicket::create([
                'user_id' => $user->id,
                'subject' => $request->subject,
                'message' => $request->message,
                'status' => 'open',
                'priority' => $request->priority ?? 'normal',
                'category' => $request->category ?? 'technical_issue',
                'meta' => [
                    'reported_at' => now(),
                    'user_type' => $user->type,
                    'device_info' => $request->header('User-Agent'),
                ]
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $ticket->addMedia($image)->toMediaCollection('attachments');
                }
            }

            // إرسال إشعار للإدارة
            $this->notificationService->created([
                'user_id' => null, // إشعار عام للإدارة
                'action' => 'technical_issue_reported',
                'message' => 'تم الإبلاغ عن مشكلة تقنية جديدة من ' . $user->name,
            ]);

            DB::commit();

            return format_response(true, 'تم إرسال تقرير المشكلة التقنية بنجاح', [
                'ticket_id' => $ticket->id,
                'subject' => $ticket->subject,
                'status' => $ticket->status,
                'priority' => $ticket->priority,
                'created_at' => $ticket->created_at,
                'attachments_count' => $ticket->getMedia('attachments')->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return format_response(false, 'حدث خطأ أثناء إرسال التقرير', [], 500);
        }
    }

    /**
     * 4. اقتراح تطوير
     */
    public function submitSuggestion(SuggestionRequest $request)
    {
        $user = Auth::user();

        DB::beginTransaction();
        try {
            $suggestion = Suggestion::create([
                'user_id' => $user->id,
                'title' => $request->title,
                'description' => $request->description,
                'status' => 'pending',
                'priority' => $request->priority ?? 'medium',
                'meta' => [
                    'submitted_at' => now(),
                    'user_type' => $user->type,
                ]
            ]);

            // Handle image uploads
            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $image) {
                    $suggestion->addMedia($image)->toMediaCollection('attachments');
                }
            }

            // إرسال إشعار للإدارة
            $this->notificationService->created([
                'user_id' => null, // إشعار عام للإدارة
                'action' => 'suggestion_submitted',
                'message' => 'تم تقديم اقتراح تطوير جديد من ' . $user->name,
            ]);

            DB::commit();

            return format_response(true, 'تم إرسال الاقتراح بنجاح', [
                'suggestion_id' => $suggestion->id,
                'title' => $suggestion->title,
                'status' => $suggestion->status,
                'priority' => $suggestion->priority,
                'created_at' => $suggestion->created_at,
                'attachments_count' => $suggestion->getMedia('attachments')->count(),
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            return format_response(false, 'حدث خطأ أثناء إرسال الاقتراح', [], 500);
        }
    }

    /**
     * جلب تذاكر الدعم الخاصة بالمستخدم
     */
    public function getMySupportTickets()
    {
        $user = Auth::user();
        
        $tickets = SupportTicket::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'subject', 'status', 'priority', 'category', 'created_at']);

        // Add attachment count to each ticket
        $tickets->each(function ($ticket) {
            $ticket->attachments_count = $ticket->getMedia('attachments')->count();
        });

        return format_response(true, 'تم جلب تذاكر الدعم بنجاح', [
            'tickets' => $tickets,
            'total' => $tickets->count()
        ]);
    }

    /**
     * جلب الاقتراحات الخاصة بالمستخدم
     */
    public function getMySuggestions()
    {
        $user = Auth::user();
        
        $suggestions = Suggestion::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get(['id', 'title', 'status', 'priority', 'created_at', 'reviewed_at']);

        // Add attachment count to each suggestion
        $suggestions->each(function ($suggestion) {
            $suggestion->attachments_count = $suggestion->getMedia('attachments')->count();
        });

        return format_response(true, 'تم جلب الاقتراحات بنجاح', [
            'suggestions' => $suggestions,
            'total' => $suggestions->count()
        ]);
    }

    /**
     * جلب تفاصيل تذكرة دعم محددة
     */
    public function getSupportTicketDetails($ticketId)
    {
        $user = Auth::user();
        
        $ticket = SupportTicket::where('id', $ticketId)
            ->where('user_id', $user->id)
            ->with(['replies.user:id,name'])
            ->first();

        if (!$ticket) {
            return format_response(false, 'التذكرة غير موجودة أو غير مخصصة لك', [], 404);
        }

        // Load attachments
        $attachments = $ticket->getMedia('attachments')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at,
            ];
        });

        return format_response(true, 'تم جلب تفاصيل التذكرة بنجاح', [
            'ticket' => $ticket,
            'attachments' => $attachments,
        ]);
    }

    /**
     * جلب تفاصيل اقتراح محدد
     */
    public function getSuggestionDetails($suggestionId)
    {
        $user = Auth::user();
        
        $suggestion = Suggestion::where('id', $suggestionId)
            ->where('user_id', $user->id)
            ->with(['reviewer:id,name'])
            ->first();

        if (!$suggestion) {
            return format_response(false, 'الاقتراح غير موجود أو غير مخصص لك', [], 404);
        }

        // Load attachments
        $attachments = $suggestion->getMedia('attachments')->map(function ($media) {
            return [
                'id' => $media->id,
                'name' => $media->name,
                'url' => $media->getUrl(),
                'thumb_url' => $media->getUrl('thumb'),
                'size' => $media->size,
                'mime_type' => $media->mime_type,
                'created_at' => $media->created_at,
            ];
        });

        return format_response(true, 'تم جلب تفاصيل الاقتراح بنجاح', [
            'suggestion' => $suggestion,
            'attachments' => $attachments,
        ]);
    }
}