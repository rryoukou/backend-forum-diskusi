<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Vote;
use App\Services\NotificationService;
use App\Services\ReputationService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class VoteController extends Controller
{
    #[OA\Post(
        path: "/vote",
        summary: "Handle upvote/downvote for posts and comments",
        tags: ["4. INTERAKSI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["target_id", "target_type", "vote_type"],
                properties: [
                    new OA\Property(property: "target_id", type: "string", example: "1"),
                    new OA\Property(property: "target_type", type: "string", enum: ["post", "comment"]),
                    new OA\Property(property: "vote_type", type: "string", enum: ["upvote", "downvote"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Vote processed successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Target not found")
        ]
    )]
    public function vote(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_id' => 'required|string',
            'target_type' => 'required|in:post,comment',
            'vote_type' => 'required|in:upvote,downvote',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $targetId = $request->target_id;
        $targetType = $request->target_type;
        $voteType = $request->vote_type;

        // Find the actual model class from morph map
        $modelClass = Relation::getMorphedModel($targetType);
        $target = $modelClass::find($targetId);

        if (! $target) {
            return response()->json(['message' => 'Target not found'], 404);
        }

        // 1. CEK SELF-VOTING: User dilarang upvote/downvote postingan sendiri
        if ($target->user_id === $userId) {
            return response()->json(['message' => 'Kamu tidak bisa memberikan vote pada konten milikmu sendiri.'], 403);
        }

        try {
            DB::beginTransaction();

            // 2. LOGIKA TOGGLE & ANTI-SPAM
            // Karena ada unique constraint di DB, kita pakai lock buat prevent race condition
            $existingVote = Vote::where([
                'user_id' => $userId,
                'target_id' => $targetId,
                'target_type' => $targetType,
            ])->lockForUpdate()->first();

            if ($existingVote) {
                if ($existingVote->vote_type === $voteType) {
                    // Toggle off if same vote type (un-vote)
                    $existingVote->delete();
                    $this->updateVoteScore($target, $voteType, -1);
                    $message = 'Vote removed';

                    // KURANGI POIN jika yang dihapus adalah upvote
                    if ($voteType === 'upvote' && $target->user_id !== $userId) {
                        ReputationService::removePoints($target->user, 10, $targetType.'_unvoted', $targetId);
                        
                        // HAPUS NOTIFIKASI jika upvote dibatalkan
                        NotificationService::remove($target->user_id, 'upvote', $targetId, $targetType, $userId);
                    }
                } else {
                    // Change vote type
                    $oldVoteType = $existingVote->vote_type;
                    $existingVote->update(['vote_type' => $voteType]);

                    // Update score: remove old, add new (net change 2 or -2)
                    $this->updateVoteScore($target, $oldVoteType, -1);
                    $this->updateVoteScore($target, $voteType, 1);
                    $message = 'Vote updated to '.$voteType;

                    // UPDATE POIN REPUTASI & NOTIFIKASI
                    if ($target->user_id !== $userId) {
                        if ($voteType === 'upvote') {
                            // Dari downvote pindah ke upvote: +10
                            ReputationService::addPoints($target->user, 10, $targetType.'_upvoted', $targetId, ucfirst($targetType).' kamu mendapatkan upvote');
                            
                            // KIRIM NOTIFIKASI jika pindah ke upvote
                            NotificationService::send($target->user_id, 'upvote', $targetId, $targetType, $userId);
                        } elseif ($oldVoteType === 'upvote') {
                            // Dari upvote pindah ke downvote: -10
                            ReputationService::removePoints($target->user, 10, $targetType.'_downvoted', $targetId);
                            
                            // HAPUS NOTIFIKASI jika pindah ke downvote
                            NotificationService::remove($target->user_id, 'upvote', $targetId, $targetType, $userId);
                        }
                    }
                }
            } else {
                // New vote
                Vote::create([
                    'user_id' => $userId,
                    'target_id' => $targetId,
                    'target_type' => $targetType,
                    'vote_type' => $voteType,
                ]);
                $this->updateVoteScore($target, $voteType, 1);

                // TAMBAH POIN jika ini upvote baru
                if ($voteType === 'upvote' && $target->user_id !== $userId) {
                    ReputationService::addPoints(
                        $target->user,
                        10,
                        $targetType.'_upvoted',
                        $targetId,
                        ucfirst($targetType).' kamu mendapatkan upvote'
                    );

                    // Trigger Notification
                    NotificationService::send($target->user_id, 'upvote', $targetId, $targetType, $userId);
                }

                $message = 'Voted '.$voteType.' successfully';
            }

            Cache::forget('posts_trending');

            DB::commit();

            return response()->json([
                'message' => $message,
                'current_score' => $target->fresh()->vote_score,
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            return response()->json(['message' => 'Failed to process vote', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update the vote score on the target model.
     */
    private function updateVoteScore($target, $voteType, $increment)
    {
        $value = $voteType === 'upvote' ? $increment : -$increment;
        $target->increment('vote_score', $value);
    }
}
