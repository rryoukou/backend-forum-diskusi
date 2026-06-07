<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class Tag extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'name',
        'slug',
        'color', // Kode warna untuk label tag di frontend
        'usage_count', // Berapa kali tag ini digunakan di postingan
    ];

    /**
     * Relasi ke Postingan yang menggunakan tag ini.
     */
    public function posts(): BelongsToMany
    {
        return $this->belongsToMany(Post::class, 'post_tags');
    }
}
