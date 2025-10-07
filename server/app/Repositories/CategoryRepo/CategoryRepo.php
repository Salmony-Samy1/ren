<?php

namespace App\Repositories\CategoryRepo;

use App\Models\Category;
use App\Repositories\BaseRepo;

class CategoryRepo extends BaseRepo implements ICategoryRepo
{
    public function __construct()
    {
        $this->model = Category::class;
    }

    public function create(array $data)
    {
        $translations = [];
        if (isset($data['en'])) {
            $translations['en'] = $data['en'];
            unset($data['en']);
        }
        if (isset($data['ar'])) {
            $translations['ar'] = $data['ar'];
            unset($data['ar']);
        }
        $category = new Category($data);
        foreach ($translations as $locale => $translation) {
            $category->translateOrNew($locale)->name = $translation['name'];
            $category->translateOrNew($locale)->description = $translation['description'];
        }
        $category->save();
        return $category;
    }
    public function update($id, array $data)
    {
        $category = $this->model::findOrFail($id);
        $translations = [];
        if (isset($data['en'])) {
            $translations['en'] = $data['en'];
            unset($data['en']);
        }
        if (isset($data['ar'])) {
            $translations['ar'] = $data['ar'];
            unset($data['ar']);
        }
        $category->fill($data);
        foreach ($translations as $locale => $translation) {
            $category->translateOrNew($locale)->name = $translation['name'];
            $category->translateOrNew($locale)->description = $translation['description'];
        }
        $category->save();
        return $category;
    }
}
