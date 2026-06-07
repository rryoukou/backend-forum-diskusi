<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Follow;
use App\Models\User;
use App\Services\NotificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class FollowController extends Controller
{
    #[OA\Post(
        path: "/follow",
        summary: "Follow or unfollow a user",
        tags: ["1. AUTH & USER MANAGEMENT"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["user_id"],
                properties: [
                    new OA\Property(property: "user_id", type: "string", example: "1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Follow toggled successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $followerId = auth()->id();
        $followingId = $request->user_id;

        if ($followerId === $followingId) {
            return response()->json(['message' => 'You cannot follow yourself'], 422);
        }

        $existingFollow = Follow::where('follower_id', $followerId)
            ->where('following_id', $followingId)
            ->first();

        if ($existingFollow) {
            $existingFollow->delete();

            return response()->json([
                'message' => 'Unfollowed successfully',
                'is_following' => false,
            ]);
        } else {
            Follow::create([
                'follower_id' => $followerId,
                'following_id' => $followingId,
            ]);

            // Trigger Notification
            NotificationService::send($followingId, 'follow', null, null, $followerId);

            return response()->json([
                'message' => 'Followed successfully',
                'is_following' => true,
            ]);
        }
    }

    #[OA\Get(
        path: "/profiles/{username}/followers",
        summary: "Get list of followers for a user",
        tags: ["1. AUTH & USER MANAGEMENT"],
        parameters: [
            new OA\Parameter(name: "username", in: "path", required: true, description: "Username", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of followers", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 404, description: "User not found")
        ]
    )]
    public function followers(string $username)
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $followers = $user->followers()
            ->select('users.id', 'users.username', 'users.avatar_url', 'users.bio')
            ->paginate(20);

        return response()->json($followers);
    }

    #[OA\Get(
        path: "/profiles/{username}/following",
        summary: "Get list of following for a user",
        tags: ["1. AUTH & USER MANAGEMENT"],
        parameters: [
            new OA\Parameter(name: "username", in: "path", required: true, description: "Username", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "List of following", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 404, description: "User not found")
        ]
    )]
    public function following(string $username)
    {
        $user = User::where('username', $username)->first();

        if (! $user) {
            return response()->json(['message' => 'User not found'], 404);
        }

        $following = $user->following()
            ->select('users.id', 'users.username', 'users.avatar_url', 'users.bio')
            ->paginate(20);

        return response()->json($following);
    }
}
