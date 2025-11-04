<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class Product extends Model
{
    /** @use HasFactory<\Database\Factories\ProductFactory> */
    use HasFactory;

    /**
     * The attributes that are mass assignable.
     *
     * @var list<string>
     */
    protected $fillable = [
        'restaurant_id',
        'name',
        'slug',
        'description',
        'price',
        'image_url',
        'is_available',
        'quantity',
        'estimated_preparation_time',
    ];

    /**
     * The attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price' => 'integer',
            'is_available' => 'boolean',
            'quantity' => 'integer',
            'estimated_preparation_time' => 'integer',
        ];
    }

    /**
     * Check if user is a customer
     */
    public function available(): bool
    {
        return $this->is_available === true;
    }

    /**
     * Scope a query to only include popular users.
     */
    protected function scopeRestaurant(Builder $query, int $restaurantId): void
    {
        $query->where('restaurant_id', $restaurantId);
    }
}
