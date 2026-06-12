<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Comment;
use App\Models\CommentEditHistory;
use App\Models\Post;
use App\Services\NotificationService;
use App\Services\ReputationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

use OpenApi\Attributes as OA;

class CommentController extends Controller
{
    #[OA\Get(
        path: "/comments",
        summary: "Display a listing of comments for a specific post",
        tags: ["3. KOMENTAR & REPLY"],
        parameters: [
            new OA\Parameter(name: "post_id", in: "query", required: true, description: "Post ID", schema: new OA\Schema(type: "string")),
            new OA\Parameter(name: "per_page", in: "query", description: "Items per page", schema: new OA\Schema(type: "integer", default: 20)),
        ],
        responses: [
            new OA\Response(response: 200, description: "List of comments", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function index(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Get top-level comments with their replies (nested)
        $comments = Comment::with([
                'user:id,username,avatar_url', 
                'children' => function($q) {
                    $q->with('user:id,username,avatar_url')->withCount('likes');
                }
            ])
            ->withCount('likes')
            ->where('post_id', $request->post_id)
            ->whereNull('parent_id')
            ->latest()
            ->paginate($request->get('per_page', 20));

        return response()->json($comments);
    }

    #[OA\Post(
        path: "/comments",
        summary: "Store a newly created comment",
        tags: ["3. KOMENTAR & REPLY"],
        security: [["bearerAuth" => []]],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["post_id", "body"],
                properties: [
                    new OA\Property(property: "post_id", type: "string", example: "1"),
                    new OA\Property(property: "parent_id", type: "string", description: "ID of parent comment if this is a reply"),
                    new OA\Property(property: "body", type: "string", example: "Great post!"),
                ]
            )
        ),
        responses: [
            new OA\Response(response: 201, description: "Comment added successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 422, description: "Validation error"),
            new OA\Response(response: 401, description: "Unauthenticated")
        ]
    )]
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'post_id' => 'required|exists:posts,id',
            'parent_id' => 'nullable|exists:comments,id',
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        // Optional: If parent_id is provided, ensure it belongs to the same post
        if ($request->parent_id) {
            $parent = Comment::find($request->parent_id);
            if ($parent->post_id !== $request->post_id) {
                return response()->json(['message' => 'Parent comment must belong to the same post'], 422);
            }
        }

        $comment = Comment::create([
            'post_id' => $request->post_id,
            'user_id' => auth()->id(),
            'parent_id' => $request->parent_id,
            'body' => $request->body,
        ]);

        // Trigger Notification
        if ($request->parent_id) {
            // Notify comment author
            $parent = Comment::find($request->parent_id);
            NotificationService::send($parent->user_id, 'reply', $comment->id, 'comment', auth()->id());
        } else {
            // Notify post author
            $post = Post::find($request->post_id);
            NotificationService::send($post->user_id, 'reply', $comment->id, 'comment', auth()->id());
        }

        Cache::forget('posts_trending');

        return response()->json([
            'message' => 'Comment added successfully',
            'data' => $comment->load('user:id,username,avatar_url'),
        ], 201);
    }

    #[OA\Put(
        path: "/comments/{id}",
        summary: "Update the specified comment",
        tags: ["3. KOMENTAR & REPLY"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Comment ID", schema: new OA\Schema(type: "string"))
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: new OA\JsonContent(
                required: ["body"],
                properties: [
                    new OA\Property(property: "body", type: "string", example: "Updated comment body")
                ]
            )
        ),
        responses: [
            new OA\Response(response: 200, description: "Comment updated successfully", content: new OA\JsonContent(type: "object")),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Comment not found"),
            new OA\Response(response: 422, description: "Validation error")
        ]
    )]
    public function update(Request $request, string $id)
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        Gate::authorize('update', $comment);

        $validator = Validator::make($request->all(), [
            'body' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        $oldBody = $comment->body;
        $comment->update([
            'body' => $request->body,
        ]);

        // Record edit history if body changed
        if ($request->body !== $oldBody) {
            CommentEditHistory::create([
                'comment_id' => $comment->id,
                'edited_by' => auth()->id(),
                'body_before' => $oldBody,
                'body_after' => $request->body,
            ]);
        }

        return response()->json([
            'message' => 'Comment updated successfully',
            'data' => $comment->load('user:id,username,avatar_url'),
        ]);
    }

    #[OA\Delete(
        path: "/comments/{id}",
        summary: "Remove the specified comment",
        tags: ["3. KOMENTAR & REPLY"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Comment ID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Comment deleted successfully"),
            new OA\Response(response: 403, description: "Unauthorized"),
            new OA\Response(response: 404, description: "Comment not found")
        ]
    )]
    public function destroy(string $id)
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        Gate::authorize('delete', $comment);

        $user = auth()->user();

        // Jika dihapus oleh moderator/admin (bukan pemilik), catat di log moderasi
        if ($comment->user_id !== $user->id) {
            \App\Models\ModerationLog::create([
                'moderator_id' => $user->id,
                'target_user_id' => $comment->user_id,
                'action_type' => 'delete_comment',
                'reason' => request('reason', 'Pelanggaran aturan komunitas'),
                'notes' => 'Comment ID: '.$comment->id.' | Content: '.Str::limit($comment->body, 100),
            ]);
        }

        $comment->delete();

        Cache::forget('posts_trending');

        return response()->json(['message' => 'Comment deleted successfully']);
    }

    #[OA\Post(
        path: "/comments/{id}/accept",
        summary: "Accept a comment as the answer for a post",
        tags: ["2. POSTINGAN"],
        security: [["bearerAuth" => []]],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Comment ID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Comment accepted as answer"),
            new OA\Response(response: 403, description: "Only the post author can accept an answer"),
            new OA\Response(response: 404, description: "Comment not found")
        ]
    )]
    public function accept(string $id)
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        Gate::authorize('accept', $comment);

        $post = $comment->post;

        // Update all comments for this post to not accepted
        Comment::where('post_id', $post->id)->update(['is_accepted' => false]);

        // Accept this comment
        $comment->update(['is_accepted' => true]);

        // Award points for accepted answer
        ReputationService::addPoints($comment->user, 15, 'answer_accepted', $comment->id, 'Jawaban diterima sebagai solusi');

        // Trigger Notification
        NotificationService::send($comment->user_id, 'answer_accepted', $comment->id, 'comment', auth()->id());

        // Check for answers accepted badges
        ReputationService::checkActivityBadges($comment->user, 'answers_accepted');

        // Update post status
        $post->update([
            'is_answered' => true,
            'accepted_answer_id' => $comment->id,
        ]);

        return response()->json(['message' => 'Comment accepted as answer']);
    }

    #[OA\Get(
        path: "/comments/{id}/history",
        summary: "Display the edit history of the specified comment",
        tags: ["3. KOMENTAR & REPLY"],
        parameters: [
            new OA\Parameter(name: "id", in: "path", required: true, description: "Comment ID", schema: new OA\Schema(type: "string"))
        ],
        responses: [
            new OA\Response(response: 200, description: "Edit history", content: new OA\JsonContent(type: "array", items: new OA\Items(type: "object"))),
            new OA\Response(response: 404, description: "Comment not found")
        ]
    )]
    public function history(string $id)
    {
        $comment = Comment::find($id);

        if (! $comment) {
            return response()->json(['message' => 'Comment not found'], 404);
        }

        $history = $comment->editHistory()
            ->with('editor:id,username,avatar_url')
            ->orderBy('edited_at', 'desc')
            ->get();

        return response()->json($history);
    }
}
