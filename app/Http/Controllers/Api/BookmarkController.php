<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Bookmark;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

use OpenApi\Attributes as OA;

class BookmarkController extends Controller
{
    #[OA\Get(
        path: "/bookmarks",
        summary: "Display a listing of the user's bookmarks",
        tags: ["2. POSTINGAN"],
        security: [["bearerAuth" => []]],
        responses: [
            new OA\Response(response: 200, description: "List of bookmarks", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function index()
    {
        $bookmarks = Bookmark::with(['post.user:id,username,avatar_url', 'post.category:id,name', 'post.tags:id,name'])
            ->where('user_id', auth()->id())
            ->latest('created_at')
            ->paginate(10);

        return response()->json($bookmarks);
    }

    #[OA\Post(
        path: "/bookmarks",
        summary: "Toggle bookmark for a post",
        tags: ["2. POSTINGAN"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["post_id"],
                properties: [
                    new OA\Property(property: "post_id", type: "string", example: "1")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Bookmark toggled", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function toggle(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $userId = auth()->id();
        $postId = $request->post_id;

        $existingBookmark = Bookmark::where('user_id', $userId)
            ->where('post_id', $postId)
            ->first();

        if ($existingBookmark) {
            $existingBookmark->delete();

            return response()->json([
                'message' => 'Post removed from bookmarks',
                'is_bookmarked' => false,
            ]);
        } else {
            Bookmark::create([
                'user_id' => $userId,
                'post_id' => $postId,
            ]);

            // KIRIM NOTIFIKASI ke pemilik postingan
            $post = Post::find($postId);
            if ($post && $post->user_id !== $userId) {
                \App\Services\NotificationService::send(
                    $post->user_id, 
                    'bookmark', 
                    $postId, 
                    'post', 
                    $userId
                );
            }

            return response()->json([
                'message' => 'Post added to bookmarks',
                'is_bookmarked' => true,
            ]);
        }
    }
}
