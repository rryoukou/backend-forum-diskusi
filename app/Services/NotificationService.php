<?php

namespace App\Services;

use App\Models\Notification;

class NotificationService
{
    /**
     * Create a new notification for a user.
     */
    public static function send($userId, $type, $referenceId = null, $referenceType = null, $actorId = null, $message = null)
    {
        // Don't notify if the actor is the same as the user receiving the notification
        if ($actorId && $userId === $actorId) {
            return;
        }

        // ANTI-SPAM: Cek apakah sudah ada notifikasi serupa yang BELUM dibaca
        $existing = Notification::where([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
        ])->first();

        if ($existing) {
            // Jika sudah ada, cukup update waktunya saja supaya naik ke atas
            // Dan update message-nya kalau ada yang baru
            $existing->update([
                'created_at' => now(),
                'message' => $message ?? $existing->message
            ]);
            return $existing;
        }

        return Notification::create([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'message' => $message,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false,
        ]);
    }

    /**
     * Remove a specific notification (e.g. when un-voting).
     */
    public static function remove($userId, $type, $referenceId, $referenceType, $actorId)
    {
        Notification::where([
            'user_id' => $userId,
            'actor_id' => $actorId,
            'type' => $type,
            'reference_id' => $referenceId,
            'reference_type' => $referenceType,
            'is_read' => false, // Hanya hapus yang belum dibaca
        ])->delete();
    }
}
