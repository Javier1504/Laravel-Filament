<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $fillable = [
        'name',
        'slug',
        'description',
        'is_active',
    ];

    protected static function booted(): void
    {
        static::saving(function (Category $category) {
            if (! $category->slug && $category->name) {
                $category->slug = Str::slug($category->name);
            }
        });
    }
}
