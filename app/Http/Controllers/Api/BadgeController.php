<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Badge;
use App\Models\PointsLog;

use OpenApi\Attributes as OA;

class BadgeController extends Controller
{
    #[OA\Get(
        path: "/badges",
        summary: "List all available badges",
        tags: ["5. GAMIFIKASI"],
        responses: [
            new OA\Response(response: 200, description: "List of badges", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object")))
        ]
    )]
    public function index()
    {
        $badges = Badge::all();

        return response()->json($badges);
    }

    #[OA\Get(
        path: "/my-badges",
        summary: "List badges earned by the authenticated user",
        tags: ["5. GAMIFIKASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of earned badges", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function myBadges()
    {
        $badges = auth()->user()->badges()->get();

        return response()->json($badges);
    }

    #[OA\Get(
        path: "/reputation-history",
        summary: "Get reputation history for the authenticated user",
        tags: ["5. GAMIFIKASI"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "Reputation history", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function reputationHistory()
    {
        $history = PointsLog::where('user_id', auth()->id())
            ->latest('created_at')
            ->paginate(20);

        $history->getCollection()->transform(function ($log) {
            if ($log->reference_id) {
                if (str_contains($log->action_type, 'post')) {
                    $post = \App\Models\Post::find($log->reference_id);
                    if ($post) {
                        $log->reference_title = $post->title;
                        $log->reference_type = 'post';
                        $log->reference_link_id = $post->id;
                    }
                } elseif (str_contains($log->action_type, 'comment') || str_contains($log->action_type, 'answer')) {
                    $comment = \App\Models\Comment::find($log->reference_id);
                    if ($comment) {
                        $log->reference_title = \Illuminate\Support\Str::limit($comment->body, 50);
                        $log->reference_type = 'comment';
                        $log->reference_link_id = $comment->post_id;
                    }
                }
            }
            return $log;
        });

        return response()->json($history);
    }
}
