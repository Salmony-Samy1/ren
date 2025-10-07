<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Traits\LogUserActivity;
use App\Http\Resources\UserCollection;
use App\Models\User;
use App\Repositories\FollowRepo\IFollowRepo;
use App\Services\NotificationService;

class FollowController extends Controller
{
    use LogUserActivity;
    public function __construct(private readonly IFollowRepo $followRepo,
                                private readonly NotificationService $notificationService)
    {
    }

    public function follow(User $user)
    {
        if ($user->id === auth()->id()) {
            return format_response(false, __('You cannot follow yourself'), code: 400);
        }

        $existing = $this->followRepo->getAll(filter: ['user_id' => $user->id, 'follower_id' => auth()->id()], query_builder: true)->first();
        if ($existing) {
            if ($existing->status === 'pending') {
                return format_response(false, __('Follow request already pending'));
            }
            if ($existing->status === 'accepted') {
                return format_response(false, __('already followed'));
            }
            // If previously rejected, re-open as pending
            $existing->update(['status' => 'pending']);
        } else {
            $this->followRepo->create([
                'user_id' => $user->id,
                'follower_id' => auth()->id(),
                'status' => 'pending',
            ]);
        }

        $this->notificationService->created([
            'user_id' => $user->id,
            'action' => 'follow_request',
            'message' => auth()->user()->name . ' طلب متابعتك.',
        ]);

        // تسجيل نشاط المتابعة
        $this->logFollow($user->id, 'follow');

        return format_response(true, __('Follow request sent'));
    }

    public function respond(User $user)
    {
        // The authenticated user is the target being followed; $user is the follower whose request is being responded to
        $request = request();
        $request->validate([
            'action' => 'required|in:accept,reject',
        ]);

        $record = $this->followRepo->getAll(filter: ['user_id' => auth()->id(), 'follower_id' => $user->id], query_builder: true)->first();

        if (!$record) {
            return format_response(false, __('No follow request found'), code: 404);
        }

        $status = $request->action === 'accept' ? 'accepted' : 'rejected';
        $record->update(['status' => $status]);

        $this->notificationService->created([
            'user_id' => $user->id,
            'action' => 'follow_request_' . $status,
            'message' => 'تم ' . ($status === 'accepted' ? 'قبول' : 'رفض') . ' طلب المتابعة.',
        ]);

        // تسجيل نشاط الرد على طلب المتابعة
        $this->logFollow($user->id, $request->action === 'accept' ? 'accept_follow' : 'reject_follow');

        return format_response(true, __('Follow request ') . $status);
    }

    public function accept(User $user)
    {
        request()->merge(['action' => 'accept']);
        return $this->respond($user);
    }

    public function reject(User $user)
    {
        request()->merge(['action' => 'reject']);
        return $this->respond($user);
    }

    public function unfollow(User $user)
    {
        $exists = $this->followRepo->getAll(filter: ['user_id' => $user->id, 'follower_id' => auth()->user()->id], query_builder: true)->first();
        if (!$exists || $exists->status !== 'accepted') {
            return format_response(false, __('not followed'));
        }
        $result = $this->followRepo->delete($exists->id);
        if ($result) {
            // تسجيل نشاط إلغاء المتابعة
            $this->logFollow($user->id, 'unfollow');
            
            return format_response(true, __('unfollowed successfully'));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }

    public function list()
    {
        $followersQuery = $this->followRepo->getAll(
            filter: ['user_id' => auth()->user()->id], 
            relations: ['follower']
        );

        $followingQuery = $this->followRepo->getAll(
            filter: ['follower_id' => auth()->user()->id], 
            relations: ['user']
        );

        $followers = $this->processFollowList($followersQuery, 'follower');
        $following = $this->processFollowList($followingQuery, 'user');

        return format_response(true, __('Follow list fetched'), [
            'followers' => $followers, 
            'following' => $following
        ]);
    }



    private function processFollowList($follows, string $relationName)
    {
        if (!$follows || $follows->isEmpty()) {
            return collect();
        }

        $users = $follows->map(function ($follow) use ($relationName) {
            return $follow->{$relationName};
        });

        $users = $users->filter(function ($user) {
            return $user && $user->type !== 'admin';
        })->values();

        if ($users->isNotEmpty()) {
            $users->load(['customerProfile', 'companyProfile']);
        }

        return $users->map(function ($user) {
            return new \App\Http\Resources\UserResource($user);
        });
    }
}
