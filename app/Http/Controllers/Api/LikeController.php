<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Like;
use App\Services\NotificationService;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class LikeController extends Controller
{
    #[OA\Post(
        path: "/like",
        summary: "Handle like/unlike for posts and comments",
        tags: ["4. INTERAKSI"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["target_id", "target_type"],
                properties: [
                    new OA\Property(property: "target_id", type: "string", example: "1"),
                    new OA\Property(property: "target_type", type: "string", enum: ["post", "comment"]),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Like toggled successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated"),
            new OA\Response(response: 404, description: "Target not found")
        ]
    )]
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'target_id' => 'required|string',
            'target_type' => 'required|in:post,comment',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $targetId = $request->target_id;
        $targetType = $request->target_type;

        // Find the actual model class from morph map
        $modelClass = Relation::getMorphedModel($targetType);
        $target = $modelClass::find($targetId);

        if (! $target) {
            return response()->json(['message' => 'Target not found'], 404);
        }

        $existingLike = Like::where([
            'user_id' => $userId,
            'target_id' => $targetId,
            'target_type' => $targetType,
        ])->first();

        if ($existingLike) {
            $existingLike->delete();

            Cache::forget('posts_trending');

            return response()->json([
                'message' => 'Unliked successfully',
                'is_liked' => false,
            ]);
        } else {
            Like::create([
                'user_id' => $userId,
                'target_id' => $targetId,
                'target_type' => $targetType,
            ]);

            // Trigger Notification
            NotificationService::send($target->user_id, 'like', $targetId, $targetType, $userId);

            Cache::forget('posts_trending');

            return response()->json([
                'message' => 'Liked successfully',
                'is_liked' => true,
            ]);
        }
    }
}
