<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TravelListing extends Model
{
    use HasFactory, HasUuids, SoftDeletes;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'user_id',
        'departure_location_id',
        'destination_location_id',
        'departure_date',
        'return_date',
        'available_weight',
        'notes',
        'active',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'departure_date' => 'date',
        'return_date' => 'date',
        'available_weight' => 'decimal:2',
        'active' => 'boolean',
    ];

    /**
     * Get the user that owns the travel listing.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the departure location associated with the travel listing.
     */
    public function departureLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'departure_location_id');
    }

    /**
     * Get the destination location associated with the travel listing.
     */
    public function destinationLocation(): BelongsTo
    {
        return $this->belongsTo(Location::class, 'destination_location_id');
    }
}
