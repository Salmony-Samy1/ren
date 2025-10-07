<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class FormTranslation extends Model
{
    public $timestamps = false;

    protected $fillable = ['label', 'help_text', 'form_id', 'locale'];

    public function form(): BelongsTo
    {
        return $this->belongsTo(Form::class);
    }
}
