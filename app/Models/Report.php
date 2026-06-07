<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;

class Report extends Model
{
    use HasUuids;

    public $timestamps = false;

    /**
     * Atribut yang dapat diisi secara massal.
     */
    protected $fillable = [
        'reporter_id', // User yang melaporkan
        'target_id', // ID objek yang dilaporkan (post/comment)
        'target_type', // Tipe objek yang dilaporkan
        'reason', // Alasan singkat (misal: 'Spam')
        'description', // Penjelasan detail laporan
        'status', // Status laporan: 'pending', 'resolved', 'dismissed'
        'resolved_by', // Moderator/Admin yang menangani
        'resolved_at',
    ];

    protected $casts = [
        'resolved_at' => 'datetime',
    ];

    /**
     * Relasi ke pembuat laporan.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    /**
     * Relasi ke objek yang dilaporkan menggunakan Polymorphic.
     */
    public function target(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Relasi ke petugas yang menangani laporan.
     */
    public function resolver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
