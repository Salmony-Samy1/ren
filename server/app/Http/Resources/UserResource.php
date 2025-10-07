<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        // Resolve unified profile via model accessor
        $profileResource = null;
        try {
            $prof = $this->profile;
            if ($prof instanceof \App\Models\CustomerProfile) {
                $profileResource = new CustomerProfileResource($prof);
            } elseif ($prof instanceof \App\Models\CompanyProfile) {
                $profileResource = new CompanyProfileResource($prof);
            }
        } catch (\Throwable $e) { $profileResource = null; }

        // Fallback: ensure profile has a stable shape even if no profile row exists
        if ($profileResource === null) {
            $profileResource = [
                'id' => null,
                'name' => $this->name,
                'company_name' => $this->company_name,
                'commercial_record' => $this->commercial_record,
                'tax_number' => $this->tax_number,
                'description' => $this->description,
                'owner' => $this->owner,
                'country_id' => $this->country_id,
                'city_id' => $this->city_id,
                'updated_at' => optional($this->updated_at)->toDateTimeString(),
            ];
        }

        // Build avatar and company logo
        $avatarUrl = null;
        try {
            if (method_exists($this, 'getFirstMediaUrl')) {
                $avatarUrl = $this->getFirstMediaUrl('avatar') ?: null;
            }
        } catch (\Throwable $e) { $avatarUrl = null; }
        
        $companyLogoUrl = null;
        try {
            $company = $this->companyProfile;
            if ($company && method_exists($company, 'getFirstMediaUrl')) {
                $companyLogoUrl = $company->getFirstMediaUrl('company_logo') ?: null;
            }
        } catch (\Throwable $e) { $companyLogoUrl = null; }

        // Get follow status for other users
        $authUser = auth()->user();
        $isFollowing = false;
        $isFollowedBy = false;

        if ($authUser && $authUser->id !== $this->id) {
            // Performant check on pre-loaded relationships from the controller
            $isFollowing = $this->whenLoaded('followers', $this->followers->isNotEmpty());
            $isFollowedBy = $this->whenLoaded('follows', $this->follows->isNotEmpty());
        }

        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'public_id' => $this->public_id,
            'national_id' => $this->national_id,
            'theme' => $this->theme,
            'full_name' => $this->when(filled($this->full_name), fn() => $this->full_name),
            'email' => $this->when($authUser && $authUser->id === $this->id, $this->email),
            'country_id' => $this->when($authUser && ($authUser->id === $this->id || $authUser->type === 'admin'), $this->country_id),
            'country_code' => $this->when($authUser && ($authUser->id === $this->id || $authUser->type === 'admin'), $this->country_code),
            'phone' => $this->when($authUser && ($authUser->id === $this->id || $authUser->type === 'admin'), $this->phone),
            'type' => $this->when($authUser && $authUser->type === 'admin', $this->type),
            'avatar_url' => $this->getFirstMediaUrl('avatar'),
            'company_logo_url' => optional($this->companyProfile)->company_logo_url ?: $companyLogoUrl,
            'wallet_balance' => $this->when($authUser && $authUser->id === $this->id, $this->balance),
            'is_deleted' => $this->trashed(),
            'updated_at' => optional($this->updated_at)->toDateTimeString(),
            'profile_status' => !is_null($this->phone_verified_at),
            'profile' => $profileResource,
            'is_following' => $isFollowing,
            'is_followed_by' => $isFollowedBy,
            'num_of_following' => $this->whenCounted('follows', $this->follows_count),
            'num_of_followers' => $this->whenCounted('followers', $this->followers_count),
            'bookings_count' => $this->whenCounted('bookings', $this->bookings_count),
            'average_rating' => $this->when(
                isset($this->reviews_avg_rating),
                round($this->reviews_avg_rating, 1)
            ),
            'role' => $this->whenHas('roles', fn() => new RoleCollection($this->roles))
        ];
    }
}