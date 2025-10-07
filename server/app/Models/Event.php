<?php


namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Event extends Model implements HasMedia
{
    use HasFactory, SoftDeletes, InteractsWithMedia;

    protected $guarded = [];

    // Append dynamic "videos" attribute to API responses
    protected $appends = ['images', 'videos'];

    protected $casts = [
        'prices_by_age' => 'array',
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'age_min' => 'integer',
        'age_max' => 'integer',
        'service_id' => 'integer',
        'max_individuals' => 'integer',
        'base_price' => 'float',
        'discount_price' => 'float',
        'hospitality_available' => 'boolean',
    ];

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    /**
     * Get the organizer (user) through the service
     */
    public function organizer()
    {
        return $this->hasOneThrough(User::class, Service::class, 'id', 'id', 'service_id', 'user_id');
    }

    /**
     * Get bookings for this event through the service
     */
    public function bookings()
    {
        return $this->hasManyThrough(Booking::class, Service::class, 'id', 'service_id', 'service_id', 'id');
    }

    /**
     * Get orders for this event through bookings
     */
    public function orders()
    {
        return $this->hasManyThrough(Order::class, Booking::class, 'service_id', 'id', 'service_id', 'order_id')
            ->where('bookings.service_id', '=', $this->service_id);
    }

    /**
     * Get reviews for this event through the service
     */
    public function reviews()
    {
        return $this->hasManyThrough(Review::class, Service::class, 'id', 'service_id', 'service_id', 'id');
    }

    /**
     * Get attendees count
     */
    public function getAttendeesCountAttribute()
    {
        return $this->bookings()->where('status', 'confirmed')->sum('total');
    }

    /**
     * Get tickets sold count
     */
    public function getTicketsSoldAttribute()
    {
        return $this->bookings()->where('status', '!=', 'cancelled')->count();
    }

    /**
     * Get tickets available count
     */
    public function getTicketsAvailableAttribute()
    {
        return max(0, $this->max_individuals - $this->tickets_sold);
    }

    /**
     * Get event status based on dates
     */
    public function getStatusAttribute()
    {
        $now = now();
        
        if ($this->start_at > $now) {
            return 'upcoming';
        } elseif ($this->start_at <= $now && $this->end_at >= $now) {
            return 'ongoing';
        } else {
            return 'completed';
        }
    }

    /**
     * Scope for upcoming events
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now());
    }

    /**
     * Scope for ongoing events
     */
    public function scopeOngoing($query)
    {
        return $query->where('start_at', '<=', now())
                   ->where('end_at', '>=', now());
    }

    /**
     * Scope for completed events
     */
    public function scopeCompleted($query)
    {
        return $query->where('end_at', '<', now());
    }

    /**
     * Scope for events by type
     */
    public function scopeByType($query, $type)
    {
        return $query->whereHas('service', function($q) use ($type) {
            $q->where('name', 'like', '%' . $type . '%');
        });
    }

    /**
     * Register media collections.
     *
     * This method is required by the HasMedia interface.
     */
    public function registerMediaCollections(): void
    {
        // allow multiple files per collection
        $this->addMediaCollection('event_images');
        $this->addMediaCollection('event_videos');
    }

    /**
     * Accessor: return images URLs from media library.
     * Always returns an array (possibly empty).
     */
    public function getImagesAttribute(): array
    {
        return $this->getMedia('event_images')->map(fn($m) => $m->getUrl())->values()->all();
    }

    /**
     * Dynamic attribute for videos URLs (derived from media library).
     */
    public function getVideosAttribute(): array
    {
        return $this->getMedia('event_videos')->map(fn($m) => $m->getUrl())->values()->all();
    }

    /**
     * Register media conversions for the model.
     *
     * @param Media|null $media
     * @return void
     */
    public function registerMediaConversions(Media $media = null): void
    {
        // $this->addMediaConversion('thumb')->width(100)->height(100);
    }
}
