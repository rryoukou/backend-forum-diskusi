<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Category extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'slug', // URL-friendly version of name
        'description',
        'parent_id', // Digunakan jika ada sub-kategori
    ];

    /**
     * Relasi ke kategori induk (parent).
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Category::class, 'parent_id');
    }

    /**
     * Relasi ke sub-kategori (children).
     */
    public function children(): HasMany
    {
        return $this->hasMany(Category::class, 'parent_id');
    }

    /**
     * Relasi ke semua postingan dalam kategori ini.
     */
    public function posts(): HasMany
    {
        return $this->hasMany(Post::class);
    }
}
