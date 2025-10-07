<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\MainServiceRequirementRequest;
use App\Http\Resources\MainServiceRequirementResource;
use App\Models\MainService;
use App\Models\Country;
use App\Models\MainServiceRequiredDocument;
use App\Services\MainServiceRequirementsService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\ValidationException;

class MainServiceRequirementsController extends Controller
{
    public function __construct(
        private readonly MainServiceRequirementsService $requirementsService
    ) {
        $this->middleware(['auth:api', 'user_type:admin', 'throttle:admin']);
    }

    /**
     * Display a listing of required documents for a main service and country.
     *
     * @param MainService $mainService
     * @param Country $country
     * @return AnonymousResourceCollection
     */
    public function index(MainService $mainService, Country $country): AnonymousResourceCollection
    {
        $requirements = $this->requirementsService->getRequiredDocuments(
            $mainService->id,
            $country->id
        );

        return MainServiceRequirementResource::collection($requirements);
    }

    /**
     * Store a newly created required document.
     *
     * @param MainServiceRequirementRequest $request
     * @param MainService $mainService
     * @param Country $country
     * @return JsonResponse
     */
    public function store(MainServiceRequirementRequest $request, MainService $mainService, Country $country): JsonResponse
    {
        try {
            $requirement = $this->requirementsService->addRequiredDocument(
                $mainService->id,
                $country->id,
                $request->validated()
            );

            return response()->json([
                'success' => true,
                'message' => 'Document requirement added successfully.',
                'data' => new MainServiceRequirementResource($requirement)
            ], 201);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors()
            ], 422);
        }
    }

    /**
     * Display the specified required document.
     *
     * @param MainService $mainService
     * @param Country $country
     * @param MainServiceRequiredDocument $requirement
     * @return JsonResponse
     */
    public function show(MainService $mainService, Country $country, MainServiceRequiredDocument $requirement): JsonResponse
    {
        // Verify that the requirement belongs to the specified main service and country
        if ($requirement->main_service_id !== $mainService->id || $requirement->country_id !== $country->id) {
            return response()->json([
                'success' => false,
                'message' => 'Requirement not found for the specified service and country.'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => new MainServiceRequirementResource($requirement)
        ]);
    }

    /**
     * Update the specified required document.
     *
     * @param MainServiceRequirementRequest $request
     * @param MainService $mainService
     * @param Country $country
     * @param MainServiceRequiredDocument $requirement
     * @return JsonResponse
     */
    public function update(
        MainServiceRequirementRequest $request,
        MainService $mainService,
        Country $country,
        MainServiceRequiredDocument $requirement
    ): JsonResponse {
        // Verify that the requirement belongs to the specified main service and country
        if ($requirement->main_service_id !== $mainService->id || $requirement->country_id !== $country->id) {
            return response()->json([
                'success' => false,
                'message' => 'Requirement not found for the specified service and country.'
            ], 404);
        }

        $updatedRequirement = $this->requirementsService->updateRequiredDocument(
            $requirement,
            $request->validated()
        );

        return response()->json([
            'success' => true,
            'message' => 'Document requirement updated successfully.',
            'data' => new MainServiceRequirementResource($updatedRequirement)
        ]);
    }

    /**
     * Remove the specified required document.
     *
     * @param MainService $mainService
     * @param Country $country
     * @param MainServiceRequiredDocument $requirement
     * @return JsonResponse
     */
    public function destroy(MainService $mainService, Country $country, MainServiceRequiredDocument $requirement): JsonResponse
    {
        // Verify that the requirement belongs to the specified main service and country
        if ($requirement->main_service_id !== $mainService->id || $requirement->country_id !== $country->id) {
            return response()->json([
                'success' => false,
                'message' => 'Requirement not found for the specified service and country.'
            ], 404);
        }

        $this->requirementsService->deleteRequiredDocument($requirement);

        return response()->json([
            'success' => true,
            'message' => 'Document requirement deleted successfully.'
        ]);
    }

    /**
     * Bulk update requirements for a main service and country.
     *
     * @param Request $request
     * @param MainService $mainService
     * @param Country $country
     * @return JsonResponse
     */
    public function bulkUpdate(Request $request, MainService $mainService, Country $country): JsonResponse
    {
        $request->validate([
            'requirements' => 'required|array',
            'requirements.*.document_type' => 'required|string|in:tourism_license,commercial_registration,food_safety_cert,catering_permit',
            'requirements.*.is_required' => 'required|boolean',
            'requirements.*.description' => 'nullable|string|max:500',
            'requirements.*.description_en' => 'nullable|string|max:500',
        ]);

        try {
            $this->requirementsService->setRequiredDocuments(
                $mainService->id,
                $country->id,
                $request->input('requirements')
            );

            $requirements = $this->requirementsService->getRequiredDocuments(
                $mainService->id,
                $country->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Document requirements updated successfully.',
                'data' => MainServiceRequirementResource::collection($requirements)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to update requirements.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Copy requirements from one country to another.
     *
     * @param Request $request
     * @param MainService $mainService
     * @param Country $country
     * @return JsonResponse
     */
    public function copyFromCountry(Request $request, MainService $mainService, Country $country): JsonResponse
    {
        $request->validate([
            'from_country_id' => 'required|exists:countries,id'
        ]);

        try {
            $this->requirementsService->copyRequirementsToCountry(
                $mainService->id,
                $request->input('from_country_id'),
                $country->id
            );

            $requirements = $this->requirementsService->getRequiredDocuments(
                $mainService->id,
                $country->id
            );

            return response()->json([
                'success' => true,
                'message' => 'Requirements copied successfully.',
                'data' => MainServiceRequirementResource::collection($requirements)
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to copy requirements.',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get requirements summary for a main service and country.
     *
     * @param MainService $mainService
     * @param Country $country
     * @return JsonResponse
     */
    public function summary(MainService $mainService, Country $country): JsonResponse
    {
        $summary = $this->requirementsService->getDocumentRequirementsSummary(
            $mainService->id,
            $country->id
        );

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get available document types.
     *
     * @return JsonResponse
     */
    public function availableDocumentTypes(): JsonResponse
    {
        $documentTypes = $this->requirementsService->getAvailableDocumentTypes();

        return response()->json([
            'success' => true,
            'data' => $documentTypes
        ]);
    }
}