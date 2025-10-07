<?php

namespace App\Http\Controllers\Admin\Users;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateUserStatusRequest;
use App\Http\Resources\UserCollection;
use App\Http\Resources\UserResource;
use App\Http\Resources\AuthenticationLogResource;
use App\Models\User;
use App\Repositories\UserRepo\IUserRepo;
use Illuminate\Http\Request;

class UsersController extends Controller
{
    public function __construct(private readonly IUserRepo $userRepo)
    {
    }

    /**
     * Display a listing of users
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'status', 'is_approved']);
        $filters['type'] = 'customer';
        $users = $this->userRepo->getAll(
            paginated: true, 
            filter: $filters, 
            withTrashed: $request->boolean('with_trashed')
        );
        
        if ($users) {
            return new UserCollection($users);
        }
        
        return format_response(false, __('Something Went Wrong'), code: 500);
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['customerProfile', 'companyProfile.city', 'companyProfile.mainService']);
        return format_response(true, __('Fetched successfully'), new UserResource($user));
    }

    /**
     * Update user status
     */
    public function updateStatus(UpdateUserStatusRequest $request, User $user)
    {
        $this->authorize('updateStatus', $user);
        $user->update(['status' => $request->validated()['status']]);
        
        return format_response(
            true, 
            __('User status updated successfully'), 
            new UserResource($user->refresh())
        );
    }

    /**
     * Approve user
     */
    public function approve(User $user)
    {
        $this->authorize('approve', $user);
        $user->update([
            'is_approved' => true,
            'approved_at' => now()
        ]);
        
        return format_response(
            true, 
            __('User approved successfully'), 
            new UserResource($user->refresh())
        );
    }

    /**
     * Reject user
     */
    public function reject(User $user)
    {
        $this->authorize('reject', $user);
        $user->update([
            'is_approved' => false,
            'approved_at' => null
        ]);
        
        return format_response(
            true, 
            __('User rejected'), 
            new UserResource($user->refresh())
        );
    }

    /**
     * Get user login history
     */
    /**
     * Fetches login history for a specific user or all users, with filtering.
     */
    public function loginHistory(Request $request, User $user = null)
    {
        $query = \App\Models\AuthenticationLog::query()
            ->where('authenticatable_type', User::class);

        // If a specific user is provided, filter by their ID.
        if ($user) {
            $query->where('authenticatable_id', $user->id);
        }

        // Apply common filters from the request
        if ($request->has('from_date')) {
            $query->whereDate('login_at', '>=', $request->from_date);
        }
        
        if ($request->has('to_date')) {
            $query->whereDate('login_at', '<=', $request->to_date);
        }
        
        if ($request->has('device_type')) {
            // A more accurate way to check device type
            $userAgent = strtolower($request->device_type);
            $query->whereRaw('LOWER(user_agent) LIKE ?', ["%{$userAgent}%"]);
        }
        
        if ($request->has('ip_address')) {
            $query->where('ip_address', $request->ip_address);
        }

        $loginHistory = $query->orderBy('login_at', 'desc')->paginate(15);
            
        // Your response structure is good, but you can simplify it
        // by passing the paginator directly to the resource if it's set up to handle it.
        // The way you have it is also correct.
        return format_response(true, __('Fetched successfully'), [
            'data' => AuthenticationLogResource::collection($loginHistory->items()),
            'meta' => [
                'current_page' => $loginHistory->currentPage(),
                'per_page' => $loginHistory->perPage(),
                'total' => $loginHistory->total(),
                'last_page' => $loginHistory->lastPage(),
            ]
        ]);
    }

    /**
     * Get user activities
     */
    public function activities(User $user)
    {
        $activities = $user->activities()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return format_response(true, __('Fetched successfully'), [
            'data' => $activities->items(),
            'meta' => [
                'current_page' => $activities->currentPage(),
                'per_page' => $activities->perPage(),
                'total' => $activities->total(),
            ]
        ]);
    }

    /**
     * Get user warnings
     */
    public function warnings(User $user)
    {
        $warnings = $user->alerts()
            ->where('severity', 'warning')
            ->orWhere('severity', 'critical')
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return format_response(true, __('Fetched successfully'), [
            'data' => $warnings->items(),
            'meta' => [
                'current_page' => $warnings->currentPage(),
                'per_page' => $warnings->perPage(),
                'total' => $warnings->total(),
            ]
        ]);
    }

    /**
     * Get user notifications
     */
    public function notifications(User $user)
    {
        $notifications = $user->notifications()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return format_response(true, __('Fetched successfully'), [
            'data' => $notifications->items(),
            'meta' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'total' => $notifications->total(),
            ]
        ]);
    }

    /**
     * Assign categories to user
     */
    public function assignUserCategories(Request $request, User $user)
    {
        $request->validate([
            'categories' => 'required|array|max:10',
            'categories.*' => 'integer|exists:categories,id',
            'user_type' => 'sometimes|string|in:premium,restricted,standard',
            'notes' => 'sometimes|string|max:1000'
        ]);

        // Sync categories with user
        $user->categories()->sync($request->categories);

        // Update user type if provided
        if ($request->has('user_type')) {
            $user->update(['user_type' => $request->user_type]);
        }

        // Add notes if provided
        if ($request->has('notes')) {
            $user->update(['notes' => $request->notes]);
        }

        return format_response(true, __('User categories assigned successfully'), [
            'data' => [
                'id' => $user->id,
                'categories' => $request->categories,
                'user_type' => $user->user_type,
                'updated_at' => $user->updated_at
            ]
        ]);
    }

    /**
     * Get user categories
     */
    public function getUserCategories(User $user)
    {
        $categories = $user->categories()->get();
        
        return format_response(true, __('Fetched successfully'), [
            'data' => [
                'user_id' => $user->id,
                'categories' => $categories,
                'user_type' => $user->user_type,
                'notes' => $user->notes
            ]
        ]);
    }

    /**
     * Add warning to user
     */
    public function addUserWarning(Request $request, User $user)
    {
        $request->validate([
            'warning' => 'required|string|max:1000',
            'type' => 'required|string|in:minor,major,critical',
            'reason' => 'sometimes|string|max:500',
            'auto_resolve_days' => 'sometimes|integer|min:1|max:365'
        ]);

        $warning = $user->warnings()->create([
            'warning' => $request->warning,
            'type' => $request->type,
            'reason' => $request->reason,
            'auto_resolve_days' => $request->auto_resolve_days
        ]);

        return format_response(true, __('Warning added successfully'), [
            'data' => [
                'id' => $warning->id,
                'warning' => $warning->warning,
                'type' => $warning->type,
                'created_at' => $warning->created_at
            ]
        ]);
    }

    /**
     * Get user warnings
     */
    public function getUserWarnings(User $user)
    {
        $warnings = $user->warnings()
            ->orderBy('created_at', 'desc')
            ->paginate(15);
            
        return format_response(true, __('Fetched successfully'), [
            'data' => $warnings->items(),
            'meta' => [
                'current_page' => $warnings->currentPage(),
                'per_page' => $warnings->perPage(),
                'total' => $warnings->total(),
                'last_page' => $warnings->lastPage(),
            ]
        ]);
    }

    /**
     * Send notification to user
     */
    public function sendUserNotification(Request $request, User $user)
    {
        $request->validate([
            'title' => 'required|string|max:150',
            'message' => 'required|string|max:2000',
            'type' => 'required|string|in:email,push,sms',
            'priority' => 'sometimes|string|in:low,medium,high',
            'scheduled_at' => 'sometimes|date|after:now'
        ]);

        // Create notification using the existing structure
        $notification = $user->notifications()->create([
            'action' => $request->type,
            'is_read' => false
        ]);

        // Create translation for the message
        $notification->translations()->create([
            'locale' => app()->getLocale(),
            'message' => $request->message
        ]);

        return format_response(true, __('Notification sent successfully'), [
            'data' => [
                'id' => $notification->id,
                'title' => $request->title,
                'status' => 'pending',
                'sent_at' => now()
            ]
        ]);
    }

    /**
     * Get user service usage statistics
     */
    public function getUserServiceUsage(Request $request, User $user)
    {
        $request->validate([
            'period' => 'sometimes|string|in:daily,weekly,monthly,yearly',
            'service_type' => 'sometimes|string|max:50',
            'location' => 'sometimes|string|max:100'
        ]);

        $period = $request->get('period', 'monthly');
        $serviceType = $request->get('service_type');
        $location = $request->get('location');

        // Get usage statistics based on period
        $usageStats = $this->getUsageStats($user, $period, $serviceType, $location);

        return format_response(true, __('Service usage statistics fetched successfully'), [
            'data' => [
                'user_id' => $user->id,
                'period' => $period,
                'usage_stats' => $usageStats
            ]
        ]);
    }

    /**
     * Get usage statistics for user
     */
    private function getUsageStats(User $user, string $period, ?string $serviceType, ?string $location)
    {
        // This is a placeholder implementation
        // You should implement actual usage statistics based on your business logic
        
        $stats = [
            [
                'service_type' => 'events',
                'usage_count' => rand(1, 10),
                'total_amount' => rand(100, 5000)
            ],
            [
                'service_type' => 'catering',
                'usage_count' => rand(1, 5),
                'total_amount' => rand(50, 2000)
            ]
        ];

        if ($serviceType) {
            $stats = array_filter($stats, function($stat) use ($serviceType) {
                return $stat['service_type'] === $serviceType;
            });
        }

        return $stats;
    }
}


