<?php

namespace App\Services\Validation;

use App\Models\CompanyLegalDocument;
use App\Models\Category;
use App\Models\MainService;
use Illuminate\Validation\ValidationException;

class ServiceCountryValidationService
{
    /**
     * Validate that the country specified for the service matches the country
     * where the user has submitted legal documents for the main service.
     *
     * @param int $userId
     * @param int $categoryId
     * @param int $countryId
     * @return void
     * @throws ValidationException
     */
    public function validateServiceCountry(int $userId, int $categoryId, int $countryId): void
    {
        // Get the category and its main service
        $category = Category::with('mainService')->find($categoryId);
        if (!$category) {
            throw ValidationException::withMessages([
                'category_id' => 'Invalid category selected.'
            ]);
        }

        $mainServiceId = $category->mainService->id;

        // Check if user has submitted legal documents for this main service
        $legalDocuments = CompanyLegalDocument::whereHas('companyProfile', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('mainServiceRequiredDocument', function ($query) use ($mainServiceId) {
            $query->where('main_service_id', $mainServiceId);
        })
        ->with('mainServiceRequiredDocument.country')
        ->get();

        if ($legalDocuments->isEmpty()) {
            throw ValidationException::withMessages([
                'country_id' => 'You must first submit legal documents for this main service before creating sub-services.'
            ]);
        }

        // Check if any of the submitted documents are for the specified country
        $hasDocumentsForCountry = $legalDocuments->filter(function ($doc) use ($countryId) {
            return (int)$doc->mainServiceRequiredDocument->country_id === (int)$countryId;
        })->isNotEmpty();

        if (!$hasDocumentsForCountry) {
            $submittedCountries = $legalDocuments->map(function ($doc) {
                return [
                    'id' => $doc->mainServiceRequiredDocument->country_id,
                    'name' => $doc->mainServiceRequiredDocument->country->name_ar
                ];
            })->unique('id');
            
            $submittedCountriesList = $submittedCountries->pluck('name')->implode(', ');
            
            throw ValidationException::withMessages([
                'country_id' => "You can only create services for countries where you have submitted legal documents. " .
                               "You have submitted documents for: {$submittedCountriesList}. " .
                               "Please select one of these countries or submit legal documents for the selected country first."
            ]);
        }

        // Check if the legal documents are approved
        $approvedDocuments = $legalDocuments->filter(function ($doc) use ($countryId) {
            // This comparison is now correct
            return (int)$doc->mainServiceRequiredDocument->country_id === (int)$countryId 
                && $doc->status->value === 'approved';
        });
        
        if ($approvedDocuments->isEmpty()) {
            throw ValidationException::withMessages([
                'country_id' => 'Your legal documents for this country are still under review. Please wait for approval before creating services.'
            ]);
        }
    }

    /**
     * Get available countries for a user based on their approved legal documents.
     *
     * @param int $userId
     * @param int $categoryId
     * @return \Illuminate\Support\Collection
     */
    public function getAvailableCountriesForUser(int $userId, int $categoryId): \Illuminate\Support\Collection
    {
        $category = Category::with('mainService')->find($categoryId);
        if (!$category) {
            return collect();
        }

        $mainServiceId = $category->mainService->id;

        return CompanyLegalDocument::whereHas('companyProfile', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('mainServiceRequiredDocument', function ($query) use ($mainServiceId) {
            $query->where('main_service_id', $mainServiceId);
        })
        ->where('status', 'approved')
        ->with('mainServiceRequiredDocument.country')
        ->get()
        ->pluck('mainServiceRequiredDocument.country')
        ->unique('id')
        ->values();
    }

    /**
     * Check if user can create services for a specific main service and country.
     *
     * @param int $userId
     * @param int $mainServiceId
     * @param int $countryId
     * @return bool
     */
    public function canCreateServiceForCountry(int $userId, int $mainServiceId, int $countryId): bool
    {
        try {
            $this->validateServiceCountry($userId, $mainServiceId, $countryId);
            return true;
        } catch (ValidationException $e) {
            return false;
        }
    }

    /**
     * Get validation summary for a user and main service.
     *
     * @param int $userId
     * @param int $mainServiceId
     * @return array
     */
    public function getValidationSummary(int $userId, int $mainServiceId): array
    {
        $legalDocuments = CompanyLegalDocument::whereHas('companyProfile', function ($query) use ($userId) {
            $query->where('user_id', $userId);
        })
        ->whereHas('mainServiceRequiredDocument', function ($query) use ($mainServiceId) {
            $query->where('main_service_id', $mainServiceId);
        })
        ->with('mainServiceRequiredDocument.country')
        ->get();

        $summary = [
            'has_documents' => $legalDocuments->isNotEmpty(),
            'approved_countries' => [],
            'pending_countries' => [],
            'total_countries' => 0
        ];

        if ($legalDocuments->isNotEmpty()) {
            $approvedCountries = $legalDocuments->where('status', 'approved')
                ->pluck('mainServiceRequiredDocument.country')
                ->unique('id')
                ->values();

            $pendingCountries = $legalDocuments->where('status', 'pending')
                ->pluck('mainServiceRequiredDocument.country')
                ->unique('id')
                ->values();

            $summary['approved_countries'] = $approvedCountries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name_ar' => $country->name_ar,
                    'name_en' => $country->name_en,
                    'code' => $country->code
                ];
            })->toArray();

            $summary['pending_countries'] = $pendingCountries->map(function ($country) {
                return [
                    'id' => $country->id,
                    'name_ar' => $country->name_ar,
                    'name_en' => $country->name_en,
                    'code' => $country->code
                ];
            })->toArray();

            $summary['total_countries'] = $legalDocuments->pluck('mainServiceRequiredDocument.country')->unique('id')->count();
        }

        return $summary;
    }
}
