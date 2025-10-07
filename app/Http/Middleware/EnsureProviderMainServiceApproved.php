<?php

namespace App\Http\Middleware;

use App\Enums\ReviewStatus;
use App\Models\Category;
use App\Models\CompanyLegalDocument;
use Closure;
use Illuminate\Http\Request;

class EnsureProviderMainServiceApproved
{
    public function handle(Request $request, Closure $next)
    {
        // Try API guard first (token-based), then fallback to default user()
        $user = $request->user('api') ?? auth('api')->user() ?? $request->user();
        if (!$user || $user->type !== 'provider') {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        if ($request->isMethod('GET')) {
            return $next($request);
        }

        $categoryId = $request->input('category_id')
            ?? $request->route('category_id')
            ?? ($request->input('service.category_id') ?? null);

        // Fallback: when updating an existing service, derive category from the bound service
        if (!$categoryId) {
            $routeService = $request->route('service');
            if ($routeService) {
                $categoryId = is_object($routeService)
                    ? ($routeService->category_id ?? null)
                    : (is_numeric($routeService)
                        ? optional(\App\Models\Service::find((int)$routeService))->category_id
                        : null);
            }
        }

        if (!$categoryId) {
            return response()->json(['message' => 'category_id is required for gating'], 422);
        }

        $category = Category::with('mainService')->find($categoryId);
        if (!$category || !$category->mainService) {
            return response()->json(['message' => 'Invalid category'], 422);
        }
        $mainServiceId = $category->main_service_id;

        $company = $user->companyProfile;
        if (!$company) {
            return response()->json(['message' => 'Company profile is required'], 422);
        }

        $exists = CompanyLegalDocument::where('company_profile_id', (int)$company->id)
            ->whereHas('mainServiceRequiredDocument', function ($query) use ($mainServiceId) {
                $query->where('main_service_id', $mainServiceId);
            })
            ->where('status', ReviewStatus::APPROVED)
            ->exists();

        if (!$exists) {
            return response()->json(['message' => 'Main service is not approved for this provider'], 403);
        }

        return $next($request);
    }
}

