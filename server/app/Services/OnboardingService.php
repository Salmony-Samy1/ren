<?php

namespace App\Services;

use App\Enums\CompanyLegalDocType;
use App\Enums\ReviewStatus;
use App\Models\MainServiceRequiredDocument;
use App\Models\CompanyProfile;
use App\Models\CompanyLegalDocument;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

class OnboardingService
{
    public function __construct(private readonly NotificationService $notifications)
    {
    }

    /**
     * Submit Tier 1 main service application with legal documents.
     *
     * @param CompanyProfile $company
     * @param int $mainServiceId
     * @param int $countryId
     * @param array $docs Array of [doc_type => UploadedFile|string]
     * @param array $meta Optional meta like expires_at per doc_type
     */
    public function submitMainServiceApplication(CompanyProfile $company, int $mainServiceId, int $countryId, array $docs, array $meta = []): array
    {

        $applicationExists = CompanyLegalDocument::query()
        ->where('company_profile_id', $company->id)
        ->whereHas('mainServiceRequiredDocument', function ($query) use ($mainServiceId) {
            $query->where('main_service_id', $mainServiceId);
        })
        ->exists();

        if ($applicationExists) {
            throw ValidationException::withMessages([
                'main_service_id' => 'An application for this service already exists for this company.',
            ]);
        }
        return DB::transaction(function () use ($company, $mainServiceId, $countryId, $docs, $meta) {
            $created = [];
            foreach ($docs as $type => $file) {
                // Normalize type
                $docType = CompanyLegalDocType::from($type);

                // Find the corresponding requirement
                $requirement = MainServiceRequiredDocument::where('main_service_id', $mainServiceId)
                    ->where('country_id', $countryId)
                    ->where('document_type', $docType)
                    ->first();

                if (!$requirement) {
                    throw ValidationException::withMessages([
                        'documents' => "No requirement found for document type {$type} in the specified service and country."
                    ]);
                }

                // Store file
                $path = is_string($file)
                    ? $file
                    : ($file?->store("legal_docs/{$company->id}", 'public'));

                $expiresAt = $meta[$type]['expires_at'] ?? null;

                $created[] = CompanyLegalDocument::create([
                    'company_profile_id' => $company->id,
                    'main_service_required_document_id' => $requirement->id,
                    'doc_type' => $docType,
                    'file_path' => $path,
                    'expires_at' => $expiresAt,
                    'status' => ReviewStatus::PENDING,
                ]);
            }

            // Notify admin user(s)
            $this->notifications->created([
                'user_id' => 1, // admin placeholder
                'action' => 'provider_main_service_application',
                'message' => "New main service application submitted for review (Service ID: {$mainServiceId}, Country ID: {$countryId}).",
            ]);

            return ['documents' => $created];
        });
    }
}

