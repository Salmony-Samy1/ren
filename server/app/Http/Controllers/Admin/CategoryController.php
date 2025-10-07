<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreCategoryRequest;
use App\Http\Resources\CategoryCollection;
use App\Http\Resources\CategoryResource;
use App\Models\Category;
use App\Repositories\CategoryRepo\ICategoryRepo;
use App\Repositories\FormRepo\IFormRepo;
use Illuminate\Support\Facades\DB;

class CategoryController extends Controller
{
    public function __construct(private readonly ICategoryRepo $repo, private readonly IFormRepo $formRepo)
    {
    }

    public function index()
    {
        $categories = $this->repo->getAll(paginated: true, withTrashed: true);
        return new CategoryCollection($categories);
    }

    public function store(StoreCategoryRequest $request)
    {
        DB::beginTransaction();
        try {
            $categoryData = $request->except('questions', 'translations');
            $category = $this->repo->create(array_merge($categoryData, $request->translations));

            if ($request->has('questions')) {
                foreach ($request->questions as $questionData) {
                    $form = $this->formRepo->create([
                        'category_id' => $category->id,
                        'translations' => $questionData['translations'],
                        'required' => $questionData['required'] ?? false,
                    ]);
                }
            }
            DB::commit();
            return new CategoryResource($category->load('questions'));
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return format_response(false, __('An error occurred while creating the category.'), code: 500);
        }
    }

    public function show(Category $category)
    {
        return format_response(true, __('category fetched successfully'), new CategoryResource($category->load('questions')));
    }

    public function update(StoreCategoryRequest $request, Category $category)
    {
        DB::beginTransaction();
        try {
            $categoryData = $request->except('questions', 'translations');
            $this->repo->update($category->id, array_merge($categoryData, $request->translations));

            if ($request->has('questions')) {
                $category->questions()->delete();
                foreach ($request->questions as $questionData) {
                    $this->formRepo->create([
                        'category_id' => $category->id,
                        'translations' => $questionData['translations'],
                        'required' => $questionData['required'] ?? false,
                    ]);
                }
            }

            DB::commit();
            $category->load('questions');
            return new CategoryResource($category);
        } catch (\Exception $e) {
            DB::rollBack();
            report($e);
            return format_response(false, __('An error occurred while updating the category.'), code: 500);
        }
    }

    public function destroy(Category $category)
    {
        $result = $this->repo->delete($category->id);
        if ($result) {
            return format_response(true, __('category deleted successfully'));
        }
        return format_response(false, __('something went wrong'), code: 500);
    }
}
