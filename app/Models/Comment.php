<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Comment extends Model
{
    use HasUuids, HasFactory;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'post_id',
        'user_id',
        'parent_id', // Digunakan untuk sistem reply (komentar bersarang)
        'body',
        'vote_score',
        'is_accepted', // Menandakan apakah komentar ini adalah jawaban yang diterima (untuk kategori tanya-jawab)
    ];

    /**
     * Relasi ke Postingan yang dikomentari.
     */
    public function post(): BelongsTo
    {
        return $this->belongsTo(Post::class);
    }

    /**
     * Relasi ke User yang menulis komentar.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke komentar induk (parent) jika ini adalah balasan.
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'parent_id');
    }

    /**
     * Relasi ke balasan-balasan (children) dari komentar ini.
     */
    public function children(): HasMany
    {
        return $this->hasMany(Comment::class, 'parent_id');
    }

    /**
     * Relasi Like (Polymorphic).
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'target');
    }

    /**
     * Relasi Vote (Polymorphic).
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'target');
    }

    /**
     * Riwayat edit komentar.
     */
    public function editHistory(): HasMany
    {
        return $this->hasMany(CommentEditHistory::class);
    }
}
