<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'slug',
        'description',
        'parent_id',
        'is_active',
        'order',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function products(): HasMany
    {
        return $this->hasMany(Product::class);
    }

    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function loadAncestors(): static
    {
        $ancestors = collect();
        $parent = $this->parent;

        while ($parent) {
            $ancestors->push($parent);
            $parent = $parent->parent;
        }

        return $this->setRelation('ancestors', $ancestors);
    }

    public function getPathAttribute(): string
    {
        $path = [$this->name];

        if ($this->relationLoaded('ancestors')) {
            foreach ($this->ancestors as $ancestor) {
                array_unshift($path, $ancestor->name);
            }
        } else {
            $category = $this->parent;
            while ($category) {
                array_unshift($path, $category->name);
                $category = $category->parent;
            }
        }

        return implode(' > ', $path);
    }
}
