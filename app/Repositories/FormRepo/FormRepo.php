<?php

namespace App\Repositories\FormRepo;

use App\Models\Form;
use App\Repositories\BaseRepo;

class FormRepo extends BaseRepo implements IFormRepo
{
    public function __construct()
    {
        $this->model = Form::class;
    }
    public function create(array $data)
    {
        $translations = $data['translations'];
        unset($data['translations']);

        $form = Form::create($data);

        foreach ($translations as $locale => $translation) {
            $translationData = [
                'form_id' => $form->id,
                'locale' => $locale,
                'label' => $translation['label'],
                'help_text' => $translation['help_text'] ?? null,
            ];
            \App\Models\FormTranslation::create($translationData);
        }
        return $form;
    }
    public function update($id, array $data)
    {
        $form = $this->model::findOrFail($id);
        $translations = $data['translations'];
        unset($data['translations']);
        $form->fill($data);
        foreach ($translations as $locale => $translation) {
            $formTranslation = $form->translateOrNew($locale);
            $formTranslation->label = $translation['label'];
            if (isset($translation['help_text'])) {
                $formTranslation->help_text = $translation['help_text'];
            }
        }
        $form->save();
        return $form;
    }
}