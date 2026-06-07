<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;

use Illuminate\Database\Eloquent\Factories\HasFactory;

class Post extends Model
{
    use HasUuids, HasFactory;

    /**
     * Atribut yang dapat diisi secara massal (mass assignable).
     */
    protected $fillable = [
        'user_id',
        'category_id',
        'title',
        'body',
        'status', // Status postingan: 'published', 'draft', 'archived'
        'view_count',
        'vote_score',
        'is_answered', // Untuk kategori tanya-jawab, menandakan apakah sudah ada jawaban yang diterima
        'accepted_answer_id', // ID komentar yang dipilih sebagai jawaban terbaik
    ];

    /**
     * Relasi ke User (Pembuat Postingan).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke Category.
     * Setiap postingan harus berada di dalam satu kategori.
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class);
    }

    /**
     * Relasi ke Tag (banyak-ke-banyak).
     * Satu postingan bisa memiliki banyak tag dan sebaliknya.
     */
    public function tags(): BelongsToMany
    {
        return $this->belongsToMany(Tag::class, 'post_tags');
    }

    /**
     * Relasi ke Comment.
     * Satu postingan bisa memiliki banyak komentar.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * Relasi ke Comment yang dipilih sebagai jawaban terbaik.
     */
    public function acceptedAnswer(): BelongsTo
    {
        return $this->belongsTo(Comment::class, 'accepted_answer_id');
    }

    /**
     * Relasi Like menggunakan Polymorphic Relationship.
     * Memungkinkan penambahan sistem Like pada model lain di masa depan.
     */
    public function likes(): MorphMany
    {
        return $this->morphMany(Like::class, 'target');
    }

    /**
     * Relasi Vote menggunakan Polymorphic Relationship.
     * Digunakan untuk sistem upvote dan downvote.
     */
    public function votes(): MorphMany
    {
        return $this->morphMany(Vote::class, 'target');
    }

    /**
     * Relasi ke Bookmark.
     * Menyimpan informasi siapa saja yang menyimpan postingan ini.
     */
    public function bookmarks(): HasMany
    {
        return $this->hasMany(Bookmark::class);
    }

    /**
     * Riwayat edit postingan.
     * Digunakan untuk melihat perubahan konten dari waktu ke waktu.
     */
    public function editHistory(): HasMany
    {
        return $this->hasMany(PostEditHistory::class);
    }
}
