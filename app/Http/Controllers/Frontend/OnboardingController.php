<?php

namespace App\Http\Controllers\Frontend;

use App\Enums\CompanyLegalDocType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Onboarding\MainServiceApplicationRequest;
use App\Http\Resources\CompanyProfileResource;
use App\Models\CompanyProfile;
use App\Models\MainService;
use App\Models\Country;
use App\Services\OnboardingService;
use App\Services\Validation\ServiceCountryValidationService;
use Illuminate\Support\Arr;
use Illuminate\Validation\ValidationException;
use App\Models\CompanyLegalDocument;
use App\Services\MainServiceRequirementsService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class OnboardingController extends Controller
{
    public function __construct(
        private readonly OnboardingService $onboarding,
        private readonly MainServiceRequirementsService $requirementsService,
        private readonly ServiceCountryValidationService $countryValidationService
    ) {
    }

    public function index()
    {
        $user = auth()->user();
        $company = $user->companyProfile;
        $companyProfileId = $user->companyProfile->id;

    $q = CompanyLegalDocument::query()->with(['companyProfile', 'mainService'])
        ->where('company_profile_id', $companyProfileId)
        ->orderByDesc('created_at');


        $docs = $q->paginate(20);
        return response()->json(['data' => $docs]);
    }

    public function submitMainService(MainServiceApplicationRequest $request)
    {
        $user = auth()->user();
        $company = $user->companyProfile;
        if (!$company) {
            return format_response(false, __('Company profile not found'), code: 404);
        }

        $mainServiceId = (int)$request->input('main_service_id');
        $countryId = (int)$request->input('country_id');

        // Validate that the main service and country exist
        $mainService = MainService::findOrFail($mainServiceId);
        $country = Country::findOrFail($countryId);

        // Expect input like documents[0][type]=commercial_registration, documents[0]=file
        $documents = [];
        $documentsMeta = [];
        
        // Get all documents data (both files and metadata)
        $allDocuments = $request->all()['documents'] ?? [];
        
        
        // Handle the correct format: documents[0][type], documents[0], documents[0][expires_at], etc.
        $documentIndexes = [];
        $allData = $request->all();
        
        // Find all document indexes from the flat structure (documents[X][type])
        foreach ($allData as $key => $value) {
            if (preg_match('/^documents\[(\d+)\]\[type\]$/', $key, $matches)) {
                $documentIndexes[] = $matches[1];
            }
        }
        
        // If no indexes found from flat structure, try to get them from files
        if (empty($documentIndexes)) {
            $files = $request->allFiles();
            if (isset($files['documents']) && is_array($files['documents'])) {
                $documentIndexes = array_keys($files['documents']);
            }
        }
        
        // If still no indexes, try to get them from $_POST directly
        if (empty($documentIndexes)) {
            foreach ($_POST as $key => $value) {
                if (preg_match('/^documents\[(\d+)\]\[type\]$/', $key, $matches)) {
                    $documentIndexes[] = $matches[1];
                }
            }
        }
        
        // Debug: Log what we found
        Log::info('Document processing debug:', [
            'allDataKeys' => array_keys($allData),
            'documentIndexes' => $documentIndexes,
            '_POSTKeys' => array_keys($_POST),
            'filesKeys' => array_keys($request->allFiles()),
            'allData' => $allData,
            '_POST' => $_POST
        ]);
        
        
        foreach ($documentIndexes as $index) {
            // Get data from $_POST (where Laravel puts the nested data)
            $docType = $_POST['documents'][$index]['type'] ?? null;
            $expiresAt = $_POST['documents'][$index]['expires_at'] ?? null;
            $status = $_POST['documents'][$index]['status'] ?? 'pending';
            
            // Get file from documents array
            $file = $allData['documents'][$index] ?? null;
            
            // Debug: Log what we found for this document
            Log::info("Processing document {$index}:", [
                'docType' => $docType,
                'expiresAt' => $expiresAt,
                'status' => $status,
                'file' => $file ? 'File uploaded' : 'No file',
                '_POST_documents' => $_POST['documents'][$index] ?? 'Not found'
            ]);
            
            if (!$docType) {
                throw ValidationException::withMessages([
                    "documents.{$index}.type" => 'نوع المستند مطلوب لكل مستند.'
                ]);
            }
            
            if (!$file) {
                throw ValidationException::withMessages([
                    "documents.{$index}" => 'الملف مطلوب لكل مستند.'
                ]);
            }
            
            // Validate that doc_type exists in enum; will throw if not valid
            $type = CompanyLegalDocType::from($docType)->value;
            
            $documents[$type] = $file;
            
            // Store metadata for this document type
            $documentsMeta[$type] = [
                'expires_at' => $expiresAt,
                'status' => $status
            ];
        }
        
        // Debug: Log what we received
        \Log::info('Documents processed:', [
            'documentIndexes' => $documentIndexes,
            'documents' => array_keys($documents),
            'documentsMeta' => $documentsMeta
        ]);

        // Validate submitted documents against requirements
        $this->requirementsService->validateDocumentSubmission($mainServiceId, $countryId, $documents);

        $result = $this->onboarding->submitMainServiceApplication($company, $mainServiceId, $countryId, $documents, $documentsMeta);

        return format_response(true, __('Submitted for review'), [
            'company' => new CompanyProfileResource($company->fresh()),
            'documents' => array_map(fn($d) => [
                'id' => $d->id,
                'doc_type' => $d->doc_type->value,
                'status' => $d->status->value,
                'file_path' => $d->file_path,
                'updated_at' => optional($d->updated_at)->toDateTimeString(),
            ], $result['documents'])
        ]);
    }

    /**
     * Get required documents for a specific main service and country.
     *
     * @param MainService $mainService
     * @param Country $country
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRequiredDocuments(MainService $mainService, Country $country)
    {
        $requirements = $this->requirementsService->getRequiredDocuments(
            $mainService->id,
            $country->id
        );

        $summary = $this->requirementsService->getDocumentRequirementsSummary(
            $mainService->id,
            $country->id
        );

        return response()->json([
            'success' => true,
            'data' => [
                'main_service' => [
                    'id' => $mainService->id,
                    'name' => $mainService->name,
                    'name_en' => $mainService->name_en,
                ],
                'country' => [
                    'id' => $country->id,
                    'name_ar' => $country->name_ar,
                    'name_en' => $country->name_en,
                    'code' => $country->code,
                ],
                'summary' => $summary,
                'requirements' => $requirements->map(function ($requirement) {
                    return [
                        'document_type' => $requirement->document_type->value,
                        'document_type_label' => ucwords(str_replace('_', ' ', $requirement->document_type->value)),
                        'is_required' => $requirement->is_required,
                        'description' => $requirement->description,
                        'description_en' => $requirement->description_en,
                    ];
                })->toArray()
            ]
        ]);
    }

    public function getAllCountryRequirements(?int $mainServiceId = null)
    {
        // 1. الحصول على المستخدم الحالي وتحديد ID الخدمة
        $user = Auth::user();
        $finalMainServiceId = $mainServiceId ?? $user->companyProfile->main_service_id;

        // 2. التحقق من وجود ID الخدمة
        if (!$finalMainServiceId) {
            return response()->json([
                'success' => false,
                'message' => 'Main service is not specified.'
            ], 400);
        }

        try {
            $mainService = MainService::findOrFail($finalMainServiceId);
            
            // 3. جلب كل الدول
            $countries = Country::all();

            // 4. تجميع المتطلبات لكل دولة
            $allRequirements = $countries->map(function ($country) use ($mainService) {
                
                $requirements = $this->requirementsService->getRequiredDocuments(
                    $mainService->id,
                    $country->id
                );

                $summary = $this->requirementsService->getDocumentRequirementsSummary(
                    $mainService->id,
                    $country->id
                );

                return [
                    'country' => [
                        'id' => $country->id,
                        'name_ar' => $country->name_ar,
                        'name_en' => $country->name_en,
                        'code' => $country->code,
                    ],
                    'summary' => $summary,
                    'requirements' => $requirements->map(function ($requirement) {
                        return [
                            'document_type' => $requirement->document_type->value,
                            'document_type_label' => ucwords(str_replace('_', ' ', $requirement->document_type->value)),
                            'is_required' => $requirement->is_required,
                            'description' => $requirement->description,
                            'description_en' => $requirement->description_en,
                        ];
                    })
                ];
            });

            return response()->json([
                'success' => true,
                'data' => [
                    'main_service' => [
                        'id' => $mainService->id,
                        'name' => $mainService->name,
                    ],
                    'countries_requirements' => $allRequirements
                ]
            ]);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid Main service ID.'
            ], 404);
        }
    }

    /**
     * Get available countries for creating services based on approved legal documents.
     *
     * @param int $categoryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getAvailableCountries(int $categoryId)
    {
        $user = auth()->user();
        if (!$user || $user->type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'Only providers can access this endpoint.'
            ], 403);
        }

        $availableCountries = $this->countryValidationService->getAvailableCountriesForUser(
            $user->id,
            $categoryId
        );

        return response()->json([
            'success' => true,
            'data' => [
                'available_countries' => $availableCountries->map(function ($country) {
                    return [
                        'id' => $country->id,
                        'name_ar' => $country->name_ar,
                        'name_en' => $country->name_en,
                        'code' => $country->code,
                        'iso_code' => $country->iso_code,
                    ];
                })->toArray(),
                'total_count' => $availableCountries->count()
            ]
        ]);
    }

    /**
     * Get validation summary for a user and main service.
     *
     * @param int $mainServiceId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getValidationSummary(int $mainServiceId)
    {
        $user = auth()->user();
        if (!$user || $user->type !== 'provider') {
            return response()->json([
                'success' => false,
                'message' => 'Only providers can access this endpoint.'
            ], 403);
        }

        $summary = $this->countryValidationService->getValidationSummary(
            $user->id,
            $mainServiceId
        );

        return response()->json([
            'success' => true,
            'data' => $summary
        ]);
    }

    /**
     * Get required documents for provider registration based on main service and country.
     *
     * @param int $mainServiceId
     * @param int $countryId
     * @return \Illuminate\Http\JsonResponse
     */
    public function getRegistrationRequirements(int $mainServiceId, int $countryId)
    {
        try {
            $mainService = MainService::findOrFail($mainServiceId);
            $country = Country::findOrFail($countryId);

            $requirements = $this->requirementsService->getRequiredDocuments($mainServiceId, $countryId);
            $summary = $this->requirementsService->getDocumentRequirementsSummary($mainServiceId, $countryId);

            return response()->json([
                'success' => true,
                'data' => [
                    'main_service' => [
                        'id' => $mainService->id,
                        'name' => $mainService->name,
                        'name_en' => $mainService->name_en,
                    ],
                    'country' => [
                        'id' => $country->id,
                        'name_ar' => $country->name_ar,
                        'name_en' => $country->name_en,
                        'code' => $country->code,
                    ],
                    'summary' => $summary,
                    'requirements' => $requirements->map(function ($requirement) {
                        return [
                            'document_type' => $requirement->document_type->value,
                            'document_type_label' => ucwords(str_replace('_', ' ', $requirement->document_type->value)),
                            'is_required' => $requirement->is_required,
                            'description' => $requirement->description,
                            'description_en' => $requirement->description_en,
                        ];
                    })->toArray()
                ]
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error retrieving requirements: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get required documents for onboarding (public endpoint)
     */
    public function getOnboardingRequirements(Request $request)
    {
        $request->validate([
            'main_service_id' => 'required|exists:main_services,id',
            'country_id' => 'required|exists:countries,id',
        ]);

        try {
            $mainService = MainService::findOrFail($request->main_service_id);
            $country = Country::findOrFail($request->country_id);

            $requirements = $this->mainServiceRequirementsService->getRequiredDocuments(
                $mainService->id,
                $country->id
            );

            $requiredDocs = $requirements->where('is_required', true)->map(function ($requirement) {
                return [
                    'document_type' => $requirement->document_type->value,
                    'document_type_name' => $requirement->document_type->name,
                    'description' => $requirement->description,
                    'description_en' => $requirement->description_en,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Required documents retrieved successfully',
                'data' => [
                    'main_service' => [
                        'id' => $mainService->id,
                        'name' => $mainService->name,
                        'name_en' => $mainService->name_en,
                    ],
                    'country' => [
                        'id' => $country->id,
                        'name' => $country->name,
                        'name_en' => $country->name_en,
                    ],
                    'required_documents' => $requiredDocs,
                    'total_required' => $requiredDocs->count(),
                ],
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to fetch required documents',
                'error' => $e->getMessage(),
            ], 500);
        }
    }
}
