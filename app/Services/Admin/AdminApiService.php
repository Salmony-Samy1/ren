<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Auth;

class AdminApiService
{
    /**
     * Make API request to backend
     */
    public static function makeRequest($method, $endpoint, $data = [])
    {
        try {
            $url = config('app.url') . $endpoint;
            
            $response = Http::withHeaders([
                'Accept' => 'application/json',
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . self::getAuthToken(),
            ])->timeout(30)->$method($url, $data);

            if ($response->successful()) {
                return $response->json();
            }
            
            return null;
        } catch (\Exception $e) {
            \Log::error('API Request Failed: ' . $e->getMessage(), [
                'method' => $method,
                'endpoint' => $endpoint,
                'data' => $data
            ]);
            return null;
        }
    }

    /**
     * Get authentication token
     */
    private static function getAuthToken()
    {
        $user = Auth::user();
        if (!$user) {
            return '';
        }

        // Try to get existing token or create new one
        $token = $user->tokens()->where('name', 'admin')->first();
        
        if (!$token) {
            $token = $user->createToken('admin');
        }

        return $token->plainTextToken ?? '';
    }

    /**
     * Get providers list
     */
    public static function getProviders($filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get provider details
     */
    public static function getProvider($providerId)
    {
        return self::makeRequest('GET', "/api/admin/providers/{$providerId}");
    }

    /**
     * Get provider performance
     */
    public static function getProviderPerformance($providerId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers/{$providerId}/performance" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get provider reviews
     */
    public static function getProviderReviews($providerId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers/{$providerId}/reviews" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get provider documents
     */
    public static function getProviderDocuments($providerId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers/{$providerId}/documents" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get provider alerts
     */
    public static function getProviderAlerts($providerId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers/{$providerId}/alerts" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get providers comparison
     */
    public static function getProvidersComparison($filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/providers/comparison/data" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Update provider status
     */
    public static function updateProviderStatus($providerId, $status)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/{$providerId}/status", [
            'status' => $status
        ]);
    }

    /**
     * Approve provider
     */
    public static function approveProvider($providerId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/{$providerId}/approve");
    }

    /**
     * Reject provider
     */
    public static function rejectProvider($providerId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/{$providerId}/reject");
    }

    /**
     * Approve review
     */
    public static function approveReview($reviewId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/reviews/{$reviewId}/approve");
    }

    /**
     * Reject review
     */
    public static function rejectReview($reviewId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/reviews/{$reviewId}/reject");
    }

    /**
     * Mark alert as read
     */
    public static function markAlertAsRead($alertId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/alerts/{$alertId}/read");
    }

    /**
     * Acknowledge alert
     */
    public static function acknowledgeAlert($alertId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/alerts/{$alertId}/acknowledge");
    }

    /**
     * Resolve alert
     */
    public static function resolveAlert($alertId)
    {
        return self::makeRequest('PATCH', "/api/admin/providers/alerts/{$alertId}/resolve");
    }

    /**
     * Get users list
     */
    public static function getUsers($filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/users" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get user details
     */
    public static function getUser($userId)
    {
        return self::makeRequest('GET', "/api/admin/users/{$userId}");
    }

    /**
     * Update user status
     */
    public static function updateUserStatus($userId, $status)
    {
        return self::makeRequest('PATCH', "/api/admin/users/{$userId}/status", [
            'status' => $status
        ]);
    }

    /**
     * Approve user
     */
    public static function approveUser($userId)
    {
        return self::makeRequest('PATCH', "/api/admin/users/{$userId}/approve");
    }

    /**
     * Reject user
     */
    public static function rejectUser($userId)
    {
        return self::makeRequest('PATCH', "/api/admin/users/{$userId}/reject");
    }

    /**
     * Get user login history
     */
    public static function getUserLoginHistory($userId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/users/{$userId}/login-history" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get user activities
     */
    public static function getUserActivities($userId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/users/{$userId}/activities" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get user warnings
     */
    public static function getUserWarnings($userId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/users/{$userId}/warnings" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }

    /**
     * Get user notifications
     */
    public static function getUserNotifications($userId, $filters = [])
    {
        $queryString = http_build_query($filters);
        $endpoint = "/api/admin/users/{$userId}/notifications" . ($queryString ? "?{$queryString}" : '');
        
        return self::makeRequest('GET', $endpoint);
    }
}


