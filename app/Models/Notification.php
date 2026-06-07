<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Notification extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'user_id', // User yang menerima notifikasi
        'actor_id', // User yang memicu notifikasi (misal: yang memberikan like)
        'type', // Jenis notifikasi (misal: 'like', 'comment', 'badge_earned')
        'message',
        'reference_id', // ID objek terkait (misal: post_id)
        'reference_type', // Tipe objek terkait ('post', 'comment', dll)
        'is_read', // Status apakah sudah dibaca
    ];

    /**
     * Relasi ke penerima notifikasi.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Relasi ke pemicu notifikasi.
     */
    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }
}
