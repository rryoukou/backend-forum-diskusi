<?php

namespace App\Services;

use App\Models\Badge;
use App\Models\PointsLog;
use App\Models\User;
use App\Models\UserBadge;
use Illuminate\Support\Facades\DB;

class ReputationService
{
    /**
     * Memberikan poin reputasi kepada user dan mengecek apakah user berhak mendapatkan badge.
     * 
     * @param User $user Objek user yang menerima poin
     * @param int $points Jumlah poin yang diberikan
     * @param string $actionType Alasan pemberian poin (misal: 'post_upvoted')
     * @param string|null $referenceId ID referensi (misal: post_id)
     * @param string|null $description Deskripsi tambahan
     */
    public static function addPoints(User $user, int $points, string $actionType, ?string $referenceId = null, ?string $description = null)
    {
        try {
            DB::beginTransaction();

            // Simpan log perubahan poin
            PointsLog::create([
                'user_id' => $user->id,
                'points' => $points,
                'action_type' => $actionType,
                'reference_id' => $referenceId,
                'description' => $description,
            ]);

            // Tambahkan poin ke total reputasi user
            $user->increment('reputation_points', $points);

            // Perbarui level user berdasarkan poin terbaru
            self::updateLevel($user);

            // Cek apakah user berhak mendapatkan badge reputasi
            self::checkReputationBadges($user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Mengurangi poin reputasi user (misal: ketika upvote dibatalkan).
     */
    public static function removePoints(User $user, int $points, string $actionType, ?string $referenceId = null)
    {
        try {
            DB::beginTransaction();

            // Kurangi poin dari total reputasi
            $user->decrement('reputation_points', $points);
            
            // Pastikan reputasi tidak bernilai negatif
            if ($user->reputation_points < 0) {
                $user->update(['reputation_points' => 0]);
            }

            self::updateLevel($user);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }

    /**
     * Memperbarui level user berdasarkan ambang batas poin tertentu.
     */
    public static function updateLevel(User $user)
    {
        $points = $user->reputation_points;
        $level = 1;

        // Logika penentuan level (bisa disesuaikan dengan kebutuhan)
        if ($points >= 2500) {
            $level = 5;
        } elseif ($points >= 1000) {
            $level = 4;
        } elseif ($points >= 500) {
            $level = 3;
        } elseif ($points >= 100) {
            $level = 2;
        }

        // Jika level berubah, update database dan kirim notifikasi
        if ($user->level !== $level) {
            $user->update(['level' => $level]);

            // Kirim notifikasi kenaikan level
            NotificationService::send($user->id, 'level_up', $level, 'level');
        }
    }

    /**
     * Cek dan berikan badge berdasarkan jumlah total poin reputasi.
     */
    public static function checkReputationBadges(User $user)
    {
        $badges = Badge::where('condition_type', 'reputation_points')
            ->where('condition_value', '<=', $user->reputation_points)
            ->get();

        foreach ($badges as $badge) {
            // Pastikan user belum memiliki badge ini sebelumnya
            $hasBadge = UserBadge::where('user_id', $user->id)
                ->where('badge_id', $badge->id)
                ->exists();

            if (! $hasBadge) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);

                // Kirim notifikasi perolehan badge baru
                NotificationService::send($user->id, 'badge_earned', $badge->id, 'badge');
            }
        }
    }

    /**
     * Cek dan berikan badge berdasarkan aktivitas tertentu (jumlah post atau jawaban diterima).
     */
    public static function checkActivityBadges(User $user, string $conditionType)
    {
        $count = 0;
        if ($conditionType === 'posts_count') {
            $count = $user->posts()->count();
        } elseif ($conditionType === 'answers_accepted') {
            $count = $user->comments()->where('is_accepted', true)->count();
        }

        $badges = Badge::where('condition_type', $conditionType)
            ->where('condition_value', '<=', $count)
            ->get();

        foreach ($badges as $badge) {
            $hasBadge = UserBadge::where('user_id', $user->id)
                ->where('badge_id', $badge->id)
                ->exists();

            if (! $hasBadge) {
                UserBadge::create([
                    'user_id' => $user->id,
                    'badge_id' => $badge->id,
                    'earned_at' => now(),
                ]);

                NotificationService::send($user->id, 'badge_earned', $badge->id, 'badge');
            }
        }
    }
}
