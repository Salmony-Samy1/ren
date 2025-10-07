<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Hobby extends Model
{
    use HasFactory, SoftDeletes;

    protected $guarded = ['id'];

    public function customers(): BelongsToMany
    {
        return $this->belongsToMany(CustomerProfile::class, 'customer_profile_hobby');
    }

    public function companies(): BelongsToMany
    {
        return $this->belongsToMany(CompanyProfile::class, 'company_profile_hobby');
    }
}

