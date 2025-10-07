<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\AuthenticationLogResource;
use App\Models\User;
use App\Models\AuthenticationLog;
use App\Repositories\UserRepo\IUserRepo;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(private readonly IUserRepo $userRepo)
    {
    }

    public function index(Request $request)
    {
        $filter = [];
        if ($request->filled('status')) $filter['status'] = $request->string('status');
        if ($request->filled('type')) $filter['type'] = $request->string('type');
        if ($request->filled('email')) $filter['email'] = $request->string('email');
        if ($request->filled('phone')) $filter['phone'] = $request->string('phone');
        $users = $this->userRepo->getAll(paginated: true, filter: $filter ?: null);
        if ($users) return new UserCollection($users);
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function store(StoreUserRequest $request)
    {
        $result = $this->userRepo->create($request->merge(['role' => 'admin'])->validated());
        if ($result) {
            $result->assignRole($request->role);
            return format_response(true, __('user created successfully'), new UserResource($result));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function show(User $user)
    {
        $this->authorize('view', $user);
        return format_response(true, __('user fetched successfully'), new UserResource($user));
    }

    public function update(StoreUserRequest $request, User $user)
    {
        $this->authorize('update', $user);
        $result = $this->userRepo->update($user->id, $request->merge(['role' => 'admin'])->validated());
        if ($result) {
            $result->syncRoles([$result->role]);
            return format_response(true, __('user updated successfully'), new UserResource($result));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function destroy(User $user)
    {
        $result = $this->userRepo->delete($user->id);
        if ($result) {
            return format_response(true, __('user deleted successfully'));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $this->authorize('updateStatus', $user);
        $data = $request->validated();
        $updated = $user->update(['status' => $data['status']]);
        if ($updated) {
            return format_response(true, __('user status updated successfully'), new UserResource($user->refresh()));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function loginActivities(Request $request, User $user)
    {
        $this->authorize('viewLoginActivities', $user);
        $query = AuthenticationLog::query()
            ->where('authenticatable_type', User::class)
            ->where('authenticatable_id', $user->id)
            ->orderByDesc('login_at');
        $logs = $query->paginate(20);
        return format_response(true, __('Fetched successfully'), [
            'data' => AuthenticationLogResource::collection($logs)->collection,
            'meta' => [
                'current_page' => $logs->currentPage(),
                'per_page' => $logs->perPage(),
                'total' => $logs->total(),
            ],
        ]);
    }

    /**
     * جلب قائمة المستخدمين مع البيانات المطلوبة للوحة التحكم
     */
    public function getUsersList(Request $request)
    {
        $this->authorize('viewAny', User::class);
        
        $query = User::with(['customerProfile', 'companyProfile'])
            ->select([
                'id', 'full_name', 'email', 'phone', 'country_code', 'type', 'status',
                'created_at', 'email_verified_at', 'phone_verified_at', 'is_approved'
            ]);

        // تطبيق الفلاتر
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('full_name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate($request->get('per_page', 15));

        return format_response(true, __('Users fetched successfully'), [
            'data' => $users->map(function($user) {
                return [
                    'id' => $user->id,
                    'full_name' => $user->full_name,
                    'email' => $user->email,
                    'phone' => $user->phone,
                    'country_code' => $user->country_code,
                    'type' => $user->type,
                    'status' => $user->status,
                    'country' => $user->customerProfile?->region?->name ?? $user->companyProfile?->city?->name ?? null,
                    'city' => $user->customerProfile?->neighbourhood?->name ?? $user->companyProfile?->city?->name ?? null,
                    'is_approved' => $user->is_approved,
                    'created_at' => $user->created_at,
                    'email_verified_at' => $user->email_verified_at,
                    'phone_verified_at' => $user->phone_verified_at,
                ];
            }),
            'meta' => [
                'current_page' => $users->currentPage(),
                'per_page' => $users->perPage(),
                'total' => $users->total(),
                'last_page' => $users->lastPage(),
            ]
        ]);
    }

    /**
     * ربط المستخدمين بالتصنيفات (الهوايات)
     */
    public function assignUserCategories(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $request->validate([
            'hobby_ids' => 'required|array',
            'hobby_ids.*' => 'exists:hobbies,id'
        ]);

        $hobbyIds = $request->hobby_ids;

        if ($user->type === 'customer' && $user->customerProfile) {
            $user->customerProfile->hobbies()->sync($hobbyIds);
        } elseif ($user->type === 'provider' && $user->companyProfile) {
            $user->companyProfile->hobbies()->sync($hobbyIds);
        } else {
            return format_response(false, __('User profile not found'), code: 404);
        }

        return format_response(true, __('User categories updated successfully'), [
            'user_id' => $user->id,
            'assigned_hobbies' => $hobbyIds
        ]);
    }

    /**
     * جلب تصنيفات المستخدم
     */
    public function getUserCategories(User $user)
    {
        $this->authorize('view', $user);
        
        $hobbies = collect();
        
        if ($user->type === 'customer' && $user->customerProfile) {
            $hobbies = $user->customerProfile->hobbies;
        } elseif ($user->type === 'provider' && $user->companyProfile) {
            $hobbies = $user->companyProfile->hobbies;
        }

        return format_response(true, __('User categories fetched successfully'), [
            'user_id' => $user->id,
            'categories' => $hobbies->map(function($hobby) {
                return [
                    'id' => $hobby->id,
                    'name' => $hobby->name,
                ];
            })
        ]);
    }

    /**
     * إضافة تحذير للمستخدم
     */
    public function addUserWarning(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $request->validate([
            'type' => 'required|string|in:warning,notice,violation',
            'severity' => 'required|string|in:low,medium,high,critical',
            'title' => 'required|string|max:255',
            'description' => 'required|string',
            'meta' => 'nullable|array'
        ]);

        $alert = \App\Models\Alert::create([
            'type' => $request->type,
            'severity' => $request->severity,
            'title' => $request->title,
            'description' => $request->description,
            'meta' => array_merge($request->meta ?? [], ['user_id' => $user->id]),
            'raised_by' => auth()->id(),
        ]);

        return format_response(true, __('Warning added successfully'), [
            'alert_id' => $alert->id,
            'user_id' => $user->id,
            'warning' => [
                'type' => $alert->type,
                'severity' => $alert->severity,
                'title' => $alert->title,
                'description' => $alert->description,
                'created_at' => $alert->created_at,
            ]
        ]);
    }

    /**
     * جلب تحذيرات المستخدم
     */
    public function getUserWarnings(User $user)
    {
        $this->authorize('view', $user);
        
        $warnings = \App\Models\Alert::whereJsonContains('meta->user_id', $user->id)
            ->orderByDesc('created_at')
            ->paginate(10);

        return format_response(true, __('User warnings fetched successfully'), [
            'user_id' => $user->id,
            'warnings' => $warnings->map(function($warning) {
                return [
                    'id' => $warning->id,
                    'type' => $warning->type,
                    'severity' => $warning->severity,
                    'title' => $warning->title,
                    'description' => $warning->description,
                    'status' => $warning->status,
                    'created_at' => $warning->created_at,
                    'acknowledged_at' => $warning->acknowledged_at,
                ];
            }),
            'meta' => [
                'current_page' => $warnings->currentPage(),
                'per_page' => $warnings->perPage(),
                'total' => $warnings->total(),
            ]
        ]);
    }

    /**
     * إرسال إشعار للمستخدم
     */
    public function sendUserNotification(Request $request, User $user)
    {
        $this->authorize('sendNotification', User::class);
        
        $request->validate([
            'action' => 'required|string',
            'message' => 'required|string',
            'locale' => 'nullable|string|in:ar,en'
        ]);

        $notification = \App\Models\UserNotification::create([
            'user_id' => $user->id,
            'action' => $request->action,
            'is_read' => false,
        ]);

        // إضافة الترجمة
        $notification->translations()->create([
            'locale' => $request->locale ?? 'ar',
            'message' => $request->message,
        ]);

        return format_response(true, __('Notification sent successfully'), [
            'notification_id' => $notification->id,
            'user_id' => $user->id,
            'action' => $notification->action,
            'message' => $request->message,
        ]);
    }

    /**
     * إعادة تعيين كلمة مرور المستخدم
     */
    public function resetPassword(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $request->validate([
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user->update([
            'password' => bcrypt($request->password)
        ]);

        // تسجيل النشاط
        \App\Models\UserActivity::create([
            'user_id' => $user->id,
            'action' => 'password_reset',
            'description' => 'تم إعادة تعيين كلمة المرور من قبل الإدارة',
            'meta' => ['reset_by' => auth()->id()]
        ]);

        return format_response(true, __('Password reset successfully'), [
            'user_id' => $user->id,
            'reset_at' => now()
        ]);
    }

    /**
     * إرسال رابط التحقق للمستخدم (للادمن بدون OTP)
     */
    public function sendVerification(Request $request, User $user)
    {
        $this->authorize('update', $user);
        
        $request->validate([
            'type' => 'required|string|in:email,phone'
        ]);

        if ($request->type === 'email' && !$user->email_verified_at) {
            // للادمن: تحقق مباشر بدون OTP
            $user->update(['email_verified_at' => now()]);
            
            \App\Models\UserActivity::create([
                'user_id' => $user->id,
                'action' => 'email_verified_admin',
                'description' => 'تم التحقق من البريد الإلكتروني من قبل الإدارة',
                'meta' => ['verified_by' => auth()->id()]
            ]);

            return format_response(true, __('Email verified successfully'), [
                'user_id' => $user->id,
                'verified_at' => now()
            ]);
        }

        if ($request->type === 'phone' && !$user->phone_verified_at) {
            // للادمن: تحقق مباشر بدون OTP
            $user->update(['phone_verified_at' => now()]);
            
            \App\Models\UserActivity::create([
                'user_id' => $user->id,
                'action' => 'phone_verified_admin',
                'description' => 'تم التحقق من الهاتف من قبل الإدارة',
                'meta' => ['verified_by' => auth()->id()]
            ]);

            return format_response(true, __('Phone verified successfully'), [
                'user_id' => $user->id,
                'verified_at' => now()
            ]);
        }

        return format_response(false, __('Already verified'), code: 400);
    }

    /**
     * إنهاء جميع الجلسات النشطة للمستخدم
     */
    public function clearSessions(Request $request, User $user)
    {
        $this->authorize('update', $user);

        // إنهاء جلسات Laravel Sanctum
        $user->tokens()->delete();

        // إنهاء جلسات Laravel Session (إذا كانت موجودة)
        \DB::table('sessions')
            ->where('user_id', $user->id)
            ->delete();

        // تسجيل النشاط
        \App\Models\UserActivity::create([
            'user_id' => $user->id,
            'action' => 'sessions_cleared',
            'description' => 'تم إنهاء جميع الجلسات النشطة من قبل الإدارة',
            'meta' => ['cleared_by' => auth()->id()]
        ]);

        return format_response(true, __('All sessions cleared successfully'), [
            'user_id' => $user->id,
            'cleared_at' => now()
        ]);
    }

    /**
     * تصدير بيانات المستخدم
     */
    public function exportData(Request $request, User $user)
    {
        $this->authorize('view', $user);

        // جمع جميع البيانات
        $userData = [
            'basic_info' => [
                'id' => $user->id,
                'full_name' => $user->full_name,
                'email' => $user->email,
                'phone' => $user->phone,
                'country_code' => $user->country_code,
                'type' => $user->type,
                'status' => $user->status,
                'created_at' => $user->created_at,
                'updated_at' => $user->updated_at,
                'email_verified_at' => $user->email_verified_at,
                'phone_verified_at' => $user->phone_verified_at,
                'is_approved' => $user->is_approved,
                'approved_at' => $user->approved_at,
                'referral_code' => $user->referral_code,
                'points' => $user->points,
                'wallet_balance' => $user->balance,
            ],
            'profile_data' => null,
            'addresses' => $user->addresses->map(function($address) {
                return [
                    'type' => $address->type,
                    'address' => $address->address,
                    'city' => $address->city,
                    'created_at' => $address->created_at,
                ];
            }),
            'bookings' => $user->bookings->map(function($booking) {
                return [
                    'id' => $booking->id,
                    'service_name' => $booking->service?->name,
                    'status' => $booking->status,
                    'total' => $booking->total,
                    'created_at' => $booking->created_at,
                ];
            }),
            'reviews' => $user->reviews->map(function($review) {
                return [
                    'id' => $review->id,
                    'service_name' => $review->service?->name,
                    'rating' => $review->rating,
                    'comment' => $review->comment,
                    'created_at' => $review->created_at,
                ];
            }),
            'activities' => \App\Models\UserActivity::where('user_id', $user->id)
                ->orderByDesc('created_at')
                ->get()
                ->map(function($activity) {
                    return [
                        'action' => $activity->action,
                        'description' => $activity->description,
                        'meta' => $activity->meta,
                        'created_at' => $activity->created_at,
                    ];
                }),
            'notifications' => $user->notifications->map(function($notification) {
                return [
                    'action' => $notification->action,
                    'message' => $notification->message,
                    'is_read' => $notification->is_read,
                    'created_at' => $notification->created_at,
                ];
            }),
            'warnings' => \App\Models\Alert::whereJsonContains('meta->user_id', $user->id)
                ->get()
                ->map(function($warning) {
                    return [
                        'type' => $warning->type,
                        'severity' => $warning->severity,
                        'title' => $warning->title,
                        'description' => $warning->description,
                        'status' => $warning->status,
                        'created_at' => $warning->created_at,
                    ];
                }),
        ];

        // إضافة بيانات الملف الشخصي
        if ($user->type === 'customer' && $user->customerProfile) {
            $userData['profile_data'] = [
                'type' => 'customer',
                'first_name' => $user->customerProfile->first_name,
                'last_name' => $user->customerProfile->last_name,
                'gender' => $user->customerProfile->gender,
                'birth_date' => $user->customerProfile->birth_date,
                'region' => $user->customerProfile->region?->name,
                'neighbourhood' => $user->customerProfile->neighbourhood?->name,
                'hobbies' => $user->customerProfile->hobbies->pluck('name'),
            ];
        } elseif ($user->type === 'provider' && $user->companyProfile) {
            $userData['profile_data'] = [
                'type' => 'provider',
                'company_name' => $user->companyProfile->company_name,
                'commercial_register' => $user->companyProfile->commercial_register,
                'city' => $user->companyProfile->city?->name,
                'main_service' => $user->companyProfile->mainService?->name,
                'address' => $user->companyProfile->address,
                'description' => $user->companyProfile->description,
            ];
        }

        // تسجيل النشاط
        \App\Models\UserActivity::create([
            'user_id' => $user->id,
            'action' => 'data_exported',
            'description' => 'تم تصدير بيانات المستخدم من قبل الإدارة',
            'meta' => ['exported_by' => auth()->id()]
        ]);

        return format_response(true, __('User data exported successfully'), $userData);
    }

    /**
     * حذف الحساب نهائياً
     */
    public function deleteAccount(Request $request, User $user)
    {
        $this->authorize('delete', $user);

        $request->validate([
            'confirm' => 'required|boolean|accepted'
        ]);

        if (!$request->confirm) {
            return format_response(false, __('Confirmation required'), code: 400);
        }

        // تسجيل النشاط قبل الحذف
        \App\Models\UserActivity::create([
            'user_id' => $user->id,
            'action' => 'account_deleted',
            'description' => 'تم حذف الحساب نهائياً من قبل الإدارة',
            'meta' => ['deleted_by' => auth()->id()]
        ]);

        // حذف المستخدم (سيتم حذف جميع البيانات المرتبطة بسبب SoftDeletes)
        $user->delete();

        return format_response(true, __('Account deleted successfully'), [
            'user_id' => $user->id,
            'deleted_at' => now()
        ]);
    }
}
