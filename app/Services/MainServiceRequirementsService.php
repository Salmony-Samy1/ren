<?php

namespace App\Services;

use App\Enums\CompanyLegalDocType;
use App\Models\MainServiceRequiredDocument;
use App\Models\MainService;
use App\Models\Country;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MainServiceRequirementsService
{
    /**
     * Get required documents for a specific main service and country.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @return Collection
     */
    public function getRequiredDocuments(int $mainServiceId, int $countryId): Collection
    {
        return MainServiceRequiredDocument::query()
            ->where('main_service_id', $mainServiceId)
            ->where('country_id', $countryId)
            ->orderBy('is_required', 'desc')
            ->orderBy('document_type')
            ->get();
    }

    /**
     * Get all required documents for a main service across all countries.
     *
     * @param int $mainServiceId
     * @return Collection
     */
    public function getAllRequiredDocumentsForMainService(int $mainServiceId): Collection
    {
        return MainServiceRequiredDocument::query()
            ->where('main_service_id', $mainServiceId)
            ->with('country')
            ->orderBy('country_id')
            ->orderBy('is_required', 'desc')
            ->orderBy('document_type')
            ->get();
    }

    /**
     * Get all required documents for a country across all main services.
     *
     * @param int $countryId
     * @return Collection
     */
    public function getAllRequiredDocumentsForCountry(int $countryId): Collection
    {
        return MainServiceRequiredDocument::query()
            ->where('country_id', $countryId)
            ->with('mainService')
            ->orderBy('main_service_id')
            ->orderBy('is_required', 'desc')
            ->orderBy('document_type')
            ->get();
    }

    /**
     * Set required documents for a main service and country.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @param array $requirements
     * @return void
     */
    public function setRequiredDocuments(int $mainServiceId, int $countryId, array $requirements): void
    {
        DB::transaction(function () use ($mainServiceId, $countryId, $requirements) {
            // Delete existing requirements for this service and country
            MainServiceRequiredDocument::query()
                ->where('main_service_id', $mainServiceId)
                ->where('country_id', $countryId)
                ->delete();

            // Create new requirements
            foreach ($requirements as $requirement) {
                MainServiceRequiredDocument::create([
                    'main_service_id' => $mainServiceId,
                    'country_id' => $countryId,
                    'document_type' => $requirement['document_type'],
                    'is_required' => $requirement['is_required'] ?? true,
                    'description' => $requirement['description'] ?? null,
                    'description_en' => $requirement['description_en'] ?? null,
                ]);
            }
        });
    }

    /**
     * Add a single required document for a main service and country.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @param array $requirement
     * @return MainServiceRequiredDocument
     */
    public function addRequiredDocument(int $mainServiceId, int $countryId, array $requirement): MainServiceRequiredDocument
    {
        // Check if requirement already exists
        $existing = MainServiceRequiredDocument::query()
            ->where('main_service_id', $mainServiceId)
            ->where('country_id', $countryId)
            ->where('document_type', $requirement['document_type'])
            ->first();

        if ($existing) {
            throw ValidationException::withMessages([
                'document_type' => 'This document requirement already exists for this service and country.'
            ]);
        }

        return MainServiceRequiredDocument::create([
            'main_service_id' => $mainServiceId,
            'country_id' => $countryId,
            'document_type' => $requirement['document_type'],
            'is_required' => $requirement['is_required'] ?? true,
            'description' => $requirement['description'] ?? null,
            'description_en' => $requirement['description_en'] ?? null,
        ]);
    }

    /**
     * Update a required document.
     *
     * @param MainServiceRequiredDocument $requirement
     * @param array $data
     * @return MainServiceRequiredDocument
     */
    public function updateRequiredDocument(MainServiceRequiredDocument $requirement, array $data): MainServiceRequiredDocument
    {
        $requirement->update([
            'is_required' => $data['is_required'] ?? $requirement->is_required,
            'description' => $data['description'] ?? $requirement->description,
            'description_en' => $data['description_en'] ?? $requirement->description_en,
        ]);

        return $requirement->fresh();
    }

    /**
     * Delete a required document.
     *
     * @param MainServiceRequiredDocument $requirement
     * @return bool
     */
    public function deleteRequiredDocument(MainServiceRequiredDocument $requirement): bool
    {
        return $requirement->delete();
    }

    /**
     * Validate document submission against requirements.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @param array $submittedDocs
     * @return void
     * @throws ValidationException
     */
    public function validateDocumentSubmission(int $mainServiceId, int $countryId, array $submittedDocs): void
    {
        $requiredDocuments = $this->getRequiredDocuments($mainServiceId, $countryId);
        $requiredDocTypes = $requiredDocuments->where('is_required', true)->pluck('document_type')->map(function($docType) {
            return $docType->value;
        })->toArray();
        $submittedDocTypes = array_keys($submittedDocs);

        $missingRequiredDocs = array_diff($requiredDocTypes, $submittedDocTypes);

        if (!empty($missingRequiredDocs)) {
            $missingDocsNames = $missingRequiredDocs; // Already converted to strings

            throw ValidationException::withMessages([
                'documents' => 'Missing required documents: ' . implode(', ', $missingDocsNames)
            ]);
        }
    }

    /**
     * Get document requirements summary for a main service and country.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @return array
     */
    public function getDocumentRequirementsSummary(int $mainServiceId, int $countryId): array
    {
        $requirements = $this->getRequiredDocuments($mainServiceId, $countryId);

        return [
            'main_service_id' => $mainServiceId,
            'country_id' => $countryId,
            'total_requirements' => $requirements->count(),
            'required_documents' => $requirements->where('is_required', true)->count(),
            'optional_documents' => $requirements->where('is_required', false)->count(),
            'documents' => $requirements->map(function ($requirement) {
                return [
                    'document_type' => $requirement->document_type->value,
                    'is_required' => $requirement->is_required,
                    'description' => $requirement->description,
                    'description_en' => $requirement->description_en,
                ];
            })->toArray()
        ];
    }

    /**
     * Copy requirements from one country to another for a main service.
     *
     * @param int $mainServiceId
     * @param int $fromCountryId
     * @param int $toCountryId
     * @return void
     */
    public function copyRequirementsToCountry(int $mainServiceId, int $fromCountryId, int $toCountryId): void
    {
        $sourceRequirements = $this->getRequiredDocuments($mainServiceId, $fromCountryId);

        $requirements = $sourceRequirements->map(function ($requirement) use ($toCountryId) {
            return [
                'document_type' => $requirement->document_type->value,
                'is_required' => $requirement->is_required,
                'description' => $requirement->description,
                'description_en' => $requirement->description_en,
            ];
        })->toArray();

        $this->setRequiredDocuments($mainServiceId, $toCountryId, $requirements);
    }

    /**
     * Get all available document types that can be required.
     *
     * @return array
     */
    public function getAvailableDocumentTypes(): array
    {
        return array_map(function ($case) {
            return [
                'value' => $case->value,
                'name' => $case->name,
                'label' => ucwords(str_replace('_', ' ', $case->value)),
            ];
        }, CompanyLegalDocType::cases());
    }
}
