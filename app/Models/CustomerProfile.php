<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CustomerProfile extends Model
{
    use SoftDeletes;
    
    protected $casts = [
        'region_id' => 'integer',
        'neigbourhood_id' => 'integer',
    ];  

    protected $guarded = ['id'];

    public function hobbies(): BelongsToMany
    {
        return $this->belongsToMany(Hobby::class, 'customer_profile_hobby');
    }

    public function region(): BelongsTo
    {
        return $this->belongsTo(Region::class);
    }

    public function neighbourhood(): BelongsTo
    {
        return $this->belongsTo(Neigbourhood::class, 'neigbourhood_id');
    }
}
