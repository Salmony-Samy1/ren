<?php

namespace App\Models;

use Astrotomic\Translatable\Translatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Form extends Model
{
    use Translatable;
    
    protected $fillable = ['category_id', 'required'];
    
    public $translatedAttributes = ['label', 'help_text'];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Override the default getRelationValue method to customize how translations are loaded.
     * This is a workaround to prevent a persistent issue where a soft-delete scope
     * is being incorrectly applied to the translation query.
     */
    public function getRelationValue($key)
    {
        if ($key === 'translations') {
            return $this->translations()->withoutGlobalScopes()->get();
        }
        return parent::getRelationValue($key);
    }

    protected function casts(): array
    {
        return [
            'required' => 'boolean',
        ];
    }
}
