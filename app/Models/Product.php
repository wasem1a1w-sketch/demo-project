<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'short_description',
        'price',
        'compare_price',
        'stock',
        'category_id',
        'is_active',
        'is_featured',
        'sku',
        'weight',
        'weight_unit',
        'primary_image_id',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'compare_price' => 'decimal:2',
        'is_active' => 'boolean',
        'is_featured' => 'boolean',
    ];

    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    public function images(): HasMany
    {
        return $this->hasMany(ProductImage::class)->orderBy('order');
    }

    public function primaryImage()
    {
        return $this->belongsTo(ProductImage::class, 'primary_image_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeFeatured($query)
    {
        return $query->where('is_featured', true);
    }

    public function getDiscountPercentageAttribute()
    {
        if (! $this->compare_price || $this->compare_price <= $this->price) {
            return null;
        }

        return round(($this->compare_price - $this->price) / $this->compare_price * 100);
    }

    public function getFormattedPriceAttribute()
    {
        return '$'.number_format($this->price, 2);
    }

    public function isInStock()
    {
        return $this->stock > 0;
    }
}
